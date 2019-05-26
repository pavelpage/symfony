<?php


namespace App\Services;


class SavedFiles
{
    private $savedFiles = [];
    private $errors = [];

    public function pushSavedFile($savedFile)
    {
        $this->savedFiles[] = $savedFile;
    }

    public function pushError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * @return mixed
     */
    public function getSavedFiles()
    {
        return $this->savedFiles;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }
}