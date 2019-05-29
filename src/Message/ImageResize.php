<?php


namespace App\Message;


class ImageResize
{
    private $imageId;
    /**
     * @var int
     */
    private $width;
    /**
     * @var int
     */
    private $height;

    /**
     * ImageResize constructor.
     * @param $imageId
     * @param int $width
     * @param int $height
     */
    public function __construct($imageId, $width = 100, $height = 100)
    {

        $this->imageId = $imageId;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getImageId(): int
    {
        return $this->imageId;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }
}