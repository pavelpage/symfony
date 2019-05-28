<?php


namespace App\Services;

use App\Business\SavedFile;
use App\Entity\Image;
use App\Kernel;
use App\Repository\ImageRepository;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

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

    /**
     * ImageService constructor.
     * @param ImageRepository $imageRepository
     * @param KernelInterface $kernel
     * @param ContainerInterface $container
     */
    public function __construct(ImageRepository $imageRepository, KernelInterface $kernel, ContainerInterface $container)
    {
        $this->originalFolderName = 'originals';
        $this->resizeFolderName = 'resize';
        $this->diskFolderName = 'upload';
        $this->imageRepository = $imageRepository;
        $this->kernel = $kernel;
        $this->container = $container;
        $this->maxFileUploadSize = round(1024*3);
    }

    /**
     * @param $filesArr UploadedFile[]
     */
    public function saveFilesAndRetrieveItems($filesArr)
    {
        $savedFiles = new SavedFiles();
        foreach ($filesArr as $file) {
            try {
                $fileName = $this->saveFileAndGetStoredName($file);
                $imageItem = $this->addImageItemToDb($fileName, $file->getClientOriginalName(), $file->getClientSize());
                //                CreateResize::dispatch($imageItem->id);
                $savedFiles->pushSavedFile($imageItem);
            } catch (\Exception $e) {
                $savedFiles->pushError([$e->getCode(), $e->getMessage(), $file->getClientOriginalName()]);
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
//                CreateResize::dispatch($imageItem->id);
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
//                CreateResize::dispatch($imageItem->id);
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
}