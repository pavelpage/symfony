<?php


namespace App\Business;


class SavedFile
{
    private $filename;
    private $originalFileName;
    private $size;

    /**
     * UploadedFile constructor.
     * @param $filename
     * @param $originalFileName
     * @param $size
     */
    public function __construct($filename, $originalFileName, $size)
    {
        $this->filename = $filename;
        $this->originalFileName = $originalFileName;
        $this->size = $size;
    }

    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return mixed
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }


}