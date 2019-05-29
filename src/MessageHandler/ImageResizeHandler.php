<?php


namespace App\MessageHandler;


use App\Message\ImageResize;
use App\Services\ImageService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ImageResizeHandler implements MessageHandlerInterface
{
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * ImageResizeHandler constructor.
     * @param ImageService $imageService
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @param ImageResize $imageResize
     * @throws \Exception
     */
    public function __invoke(ImageResize $imageResize)
    {
        $this->imageService->createResize($imageResize->getImageId(), $imageResize->getWidth(), $imageResize->getHeight());
    }
}