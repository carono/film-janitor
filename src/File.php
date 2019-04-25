<?php

namespace carono\janitor;

use yii\helpers\ArrayHelper;

/**
 * Class File
 *
 * @package carono\janitor
 */
class File
{
    public $filePath;
    public $indexName = 0;
    public $suffix;
    public $reformedName;
    public $customName;

    /**
     * @return array
     */
    protected function getStoredSerials()
    {
        if (!file_exists(Cli::$cacheFile)) {
            return [];
        }
        return json_decode(file_get_contents(Cli::$cacheFile), true) ?: [];
    }

    public function removeStoreSerialName()
    {
        $serials = $this->getStoredSerials();
        unset($serials[$this->getParentFolder()]);
        file_put_contents(Cli::$cacheFile, json_encode($serials));
    }

    /**
     * @return mixed
     */
    public function getStoredSerialName()
    {
        if ($this->isFilm()) {
            return false;
        }
        if ($data = ArrayHelper::getValue($this->getStoredSerials(), $this->getParentFolder(), [])) {
            return $data['title'] . ' ' . $this->formSuffix();
        }
        return false;
    }

    /**
     * @param $title
     */
    public function storeSerialName()
    {
        $title = $this->reformedName;
        if (!$this->getStoredSerialName()) {
            $serials = $this->getStoredSerials();
            $serials[$this->getParentFolder()] = ['title' => $title, 'suffix' => $this->suffix];
            file_put_contents(Cli::$cacheFile, json_encode($serials));
        }
    }

    /**
     * @return string
     */
    public function getParentFolder()
    {
        return basename($this->getFolder());
    }

    /**
     * @return mixed
     */
    public function getFolder()
    {
        return pathinfo($this->filePath, PATHINFO_DIRNAME);
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return pathinfo($this->filePath, PATHINFO_BASENAME);
    }

    /**
     * @return bool
     */
    public function isFilm()
    {
        return !$this->getEpisodeNumber() && !$this->getSeasonNumber();
    }

    /**
     * @return bool
     */
    public function isSerial()
    {
        return !$this->isFilm();
    }

    /**
     * @return bool
     */
    public function getYear()
    {
        return Cli::extractYear(Cli::clearName($this->filePath));
    }

    /**
     * @param null $request
     * @return mixed|string
     */
    public function formFilmNames($request = null)
    {
        $search = $request ?: $this->filePath;
        return Cli::getRealFilmNames($search);
    }

    /**
     * @return string
     */
    public function formSuffix()
    {
        return Cli::getEpisodeName($this->getSeasonNumber(), $this->getEpisodeNumber());
    }

    public function getRenamed()
    {
        if (!file_exists(Cli::$renamedFile)) {
            return [];
        }
        return json_decode(file_get_contents(Cli::$renamedFile), true) ?: [];
    }

    public function wasRenamed()
    {
        $renamed = $this->getRenamed();
        return in_array($this->filePath, $renamed);
    }


    /**
     * @param null $request
     * @return mixed|string
     */
    public function searchFilmName($request = null)
    {
        $name = ArrayHelper::getValue($this->formFilmNames($request), $this->indexName);
        if (!$name) {
            $this->reformedName = '';
            $this->suffix = '';
            return '';
        }
        if ($year = Cli::extractYear($this->filePath)) {
            $name .= " ($year)";
        }
        $this->reformedName = $name;
        if ($this->isSerial()) {
            $this->suffix = $this->formSuffix();
            $name .= ' ' . $this->suffix;
        }
        return $name;
    }

    /**
     * @return string
     */
    public function getReformedFilePath()
    {
        $name = $this->getStoredSerialName() ?: $this->searchFilmName($this->customName);
        return $this->getFolder() . DIRECTORY_SEPARATOR . $name . '.' . pathinfo($this->filePath, PATHINFO_EXTENSION);
    }

    /**
     * @return mixed
     */
    public function getReformedFileName()
    {
        return pathinfo($this->getReformedFilePath(), PATHINFO_BASENAME);
    }

    /**
     * @return int|null
     */
    public function getEpisodeNumber()
    {
        return Cli::getEpisodeNumberFromName($this->filePath);
    }


    public function storeRename()
    {
        if (!$this->wasRenamed()) {
            $renamed = $this->getRenamed();
            $renamed[] = $this->filePath;
            file_put_contents(Cli::$renamedFile, json_encode($renamed));
        }
    }

    public function renameFile()
    {
        if (!rename($this->filePath, $newPath = $this->getReformedFilePath())) {
            dir("Fail rename $this->filePath, to $newPath");
        }
        $this->storeRename();
    }

    /**
     * @return int|null
     */
    public function getSeasonNumber()
    {
        return Cli::getSeasonNumberFromName($this->filePath) ?: Cli::getSeasonNumberFromName($this->getFolder());
    }

    /**
     * File constructor.
     *
     * @param $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }
}