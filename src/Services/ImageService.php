<?php


namespace App\Services;

use App\Business\SavedFile;
use App\Entity\Image;
use App\Kernel;
use App\Repository\ImageRepository;
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
     * @param $extension
     * @return string
     * @throws \Exception
     */
    public function generateUniqueName($extension)
    {
        $randomString = md5(uniqid()).'-'.microtime(true);
        $newName = $randomString . '.' . $extension;
        return $newName;
    }
}