<?php


namespace App\Services;

use App\Business\SavedFile;
use App\Entity\Image;
use App\Kernel;
use App\Message\ImageResize;
use App\Repository\ImageRepository;
use GuzzleHttp\Client;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Intervention\Image\ImageManager;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as DependencyContainer;

class ImageService
{
    private $originalFolderName;
    private $resizeFolderName;
    private $diskFolderName;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var Kernel
     */
    private $kernel;
    /**
     * @var ContainerInterface
     */
    private $container;

    /*
     * @var int
     */
    private $maxFileUploadSize;

    /** @var ImageManager  */
    private $imageManager;

    /** @var \Symfony\Component\Asset\Packages  */
    private $assetManager;

    /**
     * @var MessageBusInterface
     */
    private $bus;
    /**
     * @var DependencyContainer
     */
    private $dependencyContainer;

    /**
     * ImageService constructor.
     * @param ImageRepository $imageRepository
     * @param KernelInterface $kernel
     * @param ContainerInterface $container
     * @param \Symfony\Component\Asset\Packages $assetsManager
     * @param MessageBusInterface $bus
     * @param DependencyContainer $dependencyContainer
     */
    public function __construct(ImageRepository $imageRepository, KernelInterface $kernel, ContainerInterface $container, \Symfony\Component\Asset\Packages $assetsManager, MessageBusInterface $bus, DependencyContainer $dependencyContainer)
    {
        $this->originalFolderName = 'originals';
        $this->resizeFolderName = 'resize';
        $this->diskFolderName = 'upload';
        $this->imageRepository = $imageRepository;
        $this->kernel = $kernel;
        $this->container = $container;
        $this->maxFileUploadSize = round(1024*3);
        $this->imageManager = new ImageManager(array('driver' => 'gd'));
        $this->filesystem = new Filesystem();
        $this->assetManager = $assetsManager;
        $this->bus = $bus;
        $this->dependencyContainer = $dependencyContainer;
    }

    /**
     * @param $filesArr UploadedFile[]
     */
    public function saveFilesAndRetrieveItems($filesArr)
    {
        $savedFiles = new SavedFiles();
        foreach ($filesArr as $file) {
            try {
                $size = $file->getSize();
                $fileName = $this->saveFileAndGetStoredName($file);
                $imageItem = $this->addImageItemToDb($fileName, $file->getClientOriginalName(), $size);
                $this->bus->dispatch(new ImageResize($imageItem->getId()));
                $savedFiles->pushSavedFile($imageItem);
            } catch (\Exception $e) {
                $savedFiles->pushError([$e->getCode(), $e->getMessage(), $file->getClientOriginalName(), $e->getTraceAsString()]);
            }
        }

        return $savedFiles;
    }

    /**
     * @param $fileName
     * @param $originalFileName
     * @param $size
     * @return Image
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function addImageItemToDb($fileName, $originalFileName, $size)
    {
        $entityManager = $this->imageRepository->getManager();

        $createdAt = new \DateTime();

        $image = new Image();
        $image->setName($fileName);
        $image->setOriginalName($originalFileName);
        $image->setFileInfo([
            'size' => $size,
        ]);
        $image->setCreatedAt($createdAt);
        $image->setUpdatedAt($createdAt);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($image);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $image;
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws \Exception
     */
    private function saveFileAndGetStoredName(UploadedFile $file)
    {
        $fileName = $this->generateUniqueName($file->getClientOriginalExtension());
        $file->move(
            $this->kernel->getProjectDir(). '/public/upload/' .$this->originalFolderName,
            $fileName
        );

        return $fileName;
    }

    /**
     * @param $urlsArr
     * @return SavedFiles
     */
    public function uploadFromUrls($urlsArr)
    {
        $savedFiles = new SavedFiles();

        foreach ($urlsArr as $url) {
            try {
                $savedFile = $this->uploadFromUrl($url);
                $imageItem = $this->addImageItemToDb($savedFile->getFilename(), $savedFile->getOriginalFileName(), $savedFile->getSize());
                $this->bus->dispatch(new ImageResize($imageItem->getId()));
                $savedFiles->pushSavedFile($imageItem);
            } catch (\Exception $e) {
                $savedFiles->pushError([$e->getCode(), $e->getMessage(), $url]);
            }
        }

        return $savedFiles;
    }

    /**
     * @param $url
     * @return SavedFile
     * @throws \Exception
     */
    public function uploadFromUrl($url)
    {
        $imageInfo = @getimagesize($url);

        if (!$imageInfo) {
            throw new \Exception('File should be image format!');
        }

        $fileExtension = $this->getExtensionFromMimeInfo($imageInfo);
        if (!in_array($fileExtension, ['jpg','jpeg', 'JPG', 'PNG', 'png', 'gif', 'bmp', 'svg'])) {
            throw new \Exception('File should be image format!');
        }

        $client = new Client();

        $response = $client->get($url)->getBody();

        $fileStream = $response->getContents();
        $originalFileName = $this->getOriginalNameFromUrl($url);
        $fileSize = $response->getSize() / 1024;

        if ($fileSize > $this->maxFileUploadSize) {
            throw new \Exception('too big image for this request');
        }

        $filename = $this->generateUniqueName($fileExtension);
        $fullPath = $this->kernel->getProjectDir(). '/public/upload/' .$this->originalFolderName .'/'.$filename;
        file_put_contents($fullPath, $fileStream);

        return new SavedFile($filename, $originalFileName, $response->getSize());
    }

    /**
     * @param $extension
     * @return string
     * @throws \Exception
     */
    public function generateUniqueName($extension)
    {
        $randomString = md5(uniqid()).'-'.time();
        $newName = $randomString . '.' . $extension;
        return $newName;
    }

    /**
     * @param $info
     * @return mixed
     */
    private function getExtensionFromMimeInfo($info)
    {
        return explode('/', $info['mime'])[1];
    }

    /**
     * @param $url
     * @return bool|string
     */
    private function getOriginalNameFromUrl($url)
    {
        return substr($url, strrpos($url, '/') + 1);
    }

    public function saveFilesFromBase64($files)
    {
        $savedFiles = new SavedFiles();

        foreach ($files as $file) {
            try {
                $savedFile = $this->saveFileFromBase64($file);
                $imageItem = $this->addImageItemToDb($savedFile->getFilename(), $savedFile->getOriginalFileName(), $savedFile->getSize());
                $this->bus->dispatch(new ImageResize($imageItem->getId()));
                $savedFiles->pushSavedFile($imageItem);
            } catch (\Exception $e) {
                $savedFiles->pushError([$e->getCode(), $e->getMessage()]);
            }
        }

        return $savedFiles;
    }

    /**
     * @param $base64String
     * @return SavedFile
     * @throws \Exception
     */
    private function saveFileFromBase64($base64String)
    {
        $data = explode(',', $base64String);
        $content = base64_decode($data[1]);
        $fileExtension = $this->getExtensionFromBase64String($base64String);
        $fileName = $this->generateUniqueName($fileExtension);

        $fullPath = $this->kernel->getProjectDir(). '/public/upload/' .$this->originalFolderName .'/'.$fileName;
        file_put_contents($fullPath, $content);

        return new SavedFile($fileName, $fileName, filesize($fullPath));
    }

    /**
     * @param $base64String
     * @return string
     * @throws \Exception
     */
    public function getExtensionFromBase64String($base64String)
    {
        $data = explode(',', $base64String);
        $mimeType = explode(':',$data[0]);
        $mimeType = $mimeType[1];

        $mimeType = substr($mimeType, 0, strpos($mimeType, ';'));

        $extensionsArr = [
            'image/gif' => 'gif',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'image/png' => 'png',
        ];

        if (!isset($extensionsArr[$mimeType])) {
            throw new \Exception('Unsupported mime type of base64 image');
        }

        return $extensionsArr[$mimeType];
    }

    /**
     * @param $imageId
     * @param int $width
     * @param int $height
     * @return string
     * @throws \Exception
     */
    public function createResize($imageId, $width = 100, $height = 100)
    {
        $imageItem = $this->imageRepository->find($imageId);
        if (!$imageItem) {
            throw new \Exception('No image with such id');
        }

        $fullPath = $this->kernel->getProjectDir(). '/public/upload/' .$this->originalFolderName .'/'.$imageItem->getName();
        $image = $this->imageManager->make(file_get_contents($fullPath));

        $publicDir = $this->kernel->getProjectDir(). '/public/'.($this->diskFolderName.'/'.$this->resizeFolderName);
        if (!is_dir($publicDir)) {
            $this->filesystem->mkdir($publicDir, 0755);
        }

        $resizeImageName = $this->getResizeImageName($imageItem->getName(), $width, $height);

        $image->resize($width, $height)->save($publicDir.'/'.$resizeImageName);

        $this->imageRepository->addResize($imageItem, $width, $height);

        return $this->assetManager->getUrl($this->diskFolderName.'/'.$this->resizeFolderName. '/' . $resizeImageName);
    }

    /**
     * @param $imageName
     * @param $width
     * @param $height
     * @return string
     */
    public function getResizeImageName($imageName, $width, $height)
    {
        return $this->getNameWithoutExtension($imageName) .
            '_[resize_' . $width . 'x' . $height . '].' .
            $this->getFileExtension($imageName);
    }

    /**
     * @param $name
     * @return bool|string
     */
    private function getNameWithoutExtension($name)
    {
        $posLastPoint = mb_strrpos($name, ".");

        if ($posLastPoint !== false) {
            $name = mb_substr($name, 0, $posLastPoint);
            return $name;
        }
        return false;
    }

    /**
     * @param $fileName
     * @return bool|string
     */
    private function getFileExtension($fileName)
    {
        $lastDotPos = mb_strrpos($fileName, '.');
        if ( !$lastDotPos ) return false;
        return mb_substr($fileName, $lastDotPos+1);
    }

    /**
     * @param $imageId
     * @return array
     * @throws \Exception
     */
    public function getImageResizes($imageId)
    {
        $imageItem = $this->imageRepository->find($imageId);
        if (!$imageItem) {
            throw new \Exception('No image with such id');
        }
        $resizes = $imageItem->getResizes();

        $result = [];
        foreach ($resizes as $resize) {

            $resizeImageName = $this->getResizeImageName($imageItem->getName(), $resize['width'], $resize['height']);

            $urlPackage = new UrlPackage($this->dependencyContainer->getParameter('app_host'), new StaticVersionStrategy(''));

            $result[] = [
                'url' => trim($urlPackage->getUrl($this->diskFolderName.'/'.$this->resizeFolderName. '/' . $resizeImageName), '?'),
                'width' => $resize['width'],
                'height' => $resize['height'],
            ];
        }

        return $result;
    }

    /**
     * @param $imageId
     * @param $width
     * @param $height
     * @return bool
     * @throws \Exception
     */
    public function deleteImageResize($imageId, $width, $height)
    {
        $imageItem = $entityManager = $this->imageRepository->find($imageId);
        if (!$imageItem) {
            throw new \Exception('No image with such id');
        }

        $resizeImageName = $this->getResizeImageName($imageItem->getName(), $width, $height);

        $publicDir = $this->kernel->getProjectDir(). '/public/'.($this->diskFolderName.'/'.$this->resizeFolderName);
        $this->filesystem->remove($publicDir . '/' . $resizeImageName);

        $this->imageRepository->deleteResize($imageItem, $width, $height);

        return true;
    }

    /**
     * @param $imageId
     * @return bool
     * @throws \Exception
     */
    public function deleteAllImageResizes($imageId)
    {
        $imageItem = $entityManager = $this->imageRepository->find($imageId);
        if (!$imageItem) {
            throw new \Exception('No image with such id');
        }
        $resizes = $imageItem->getResizes();

        foreach ($resizes as $resize) {
            $this->deleteImageResize($imageId, $resize['width'], $resize['height']);
        }

        return true;
    }
}