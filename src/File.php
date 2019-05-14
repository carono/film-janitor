<?php

namespace carono\janitor;

use carono\janitor\engines\EngineAbstract;
use carono\janitor\engines\EngineInterface;
use carono\janitor\helpers\ArrayHelper;

/**
 * Class File
 *
 * @package carono\janitor
 */
class File
{
    /**
     * @var string
     */
    protected $filePath;
    /**
     * @var array
     */
    protected $movieNames = [];
    /**
     * @var EngineAbstract
     */
    protected $engine;

    /**
     * @return string
     */
    public function getParentFolder()
    {
        return basename($this->getFolder());
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return (string)pathinfo($this->filePath, PATHINFO_DIRNAME);
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return (string)pathinfo($this->filePath, PATHINFO_FILENAME);
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return (string)pathinfo($this->filePath, PATHINFO_EXTENSION);
    }

    /**
     * @return array
     */
    protected function getStoredSerials()
    {
        return $this->getCacheValue('serial-names') ?: [];
    }

    /**
     * @return mixed
     */
    public function getStoredSerialData()
    {
        if ($this->isFilm()) {
            return [];
        }
        return ArrayHelper::getValue($this->getStoredSerials(), $this->getParentFolder(), []);
    }

    /**
     *
     */
    public function restoreSerialData()
    {
        if ($data = $this->getStoredSerialData()) {
            $this->movieNames = $data['movieNames'];
        }
    }

    /**
     * @return bool
     */
    public function getStoredSerialName()
    {
        if ($data = $this->getStoredSerialData()) {
            return $data['newName'];
        }
        return false;
    }

    /**
     * @param $newName
     */
    public function storeSerialName($newName)
    {
        $title = $this->getReformFilmName($newName);
        if ($this->isSerial() && !$this->getStoredSerialData()) {
            $serials = $this->getStoredSerials();
            $data = [
                'title' => $title,
                'newName' => $newName,
                'movieNames' => $this->movieNames
            ];
            $serials[$this->getParentFolder()] = $data;
            $this->setCacheValue('serial-names', $serials);
        }
    }

    /**
     * @return bool
     */
    public function isFilm()
    {
        return !$this->getSeasonNumber() && !$this->getEpisodeNumber();
    }

    /**
     * @return bool
     */
    public function isSerial()
    {
        return !$this->isFilm();
    }

    /**
     * @param null $request
     * @return string[]
     */
    public function searchFilmNames($request = null)
    {
        $search = $request ?: $this->filePath;
        if (!isset($this->movieNames[$search])) {
            $this->movieNames[$search] = $this->engine->getTitles($search, $this);
        }
        return $this->movieNames[$search];
    }

    /**
     * @param $newName
     * @return string
     */
    public function getReformFilePath($newName)
    {
        return $this->getFolder() . DIRECTORY_SEPARATOR . $this->getReformFileName($newName);
    }

    /**
     * @param $newName
     * @return string
     */
    public function getReformFileName($newName)
    {
        return $this->getReformFilmName($newName) . '.' . $this->getFileExtension();
    }

    /**
     * @param $newName
     * @return string
     */
    public function getReformFilmName($newName)
    {
        return $this->engine->reformFilmName($this, $newName);
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return Cli::getEpisodeName($this->getSeasonNumber(), $this->getEpisodeNumber());
    }

    /**
     * @return int|null
     */
    public function getEpisodeNumber()
    {
        if (!$episode = $this->engine->parseSerialEpisodeNumber($this->filePath)) {
            return $this->getSeasonNumber() ? 1 : null;
        }
        return $episode;
    }

    /**
     * @return int|null
     */
    public function getSeasonNumber()
    {
        return $this->engine->parseSerialSeason($this->filePath) ?: $this->engine->parseSerialSeason($this->getFolder());
    }

    protected function setCacheValue($key, $data)
    {
        $class = $this->engine;
        $class::setCacheValue($key, $data);
    }

    protected function getCacheValue($key)
    {
        $class = $this->engine;
        return $class::getCacheValue($key);
    }

    /**
     * @return bool
     */
    public function wasRenamed()
    {
        $key = 'store-file:' . md5($this->filePath);
        return (bool)$this->getCacheValue($key);
    }

    public function storeRename()
    {
        $key = 'store-file:' . md5($this->filePath);
        $this->setCacheValue($key, 1);
    }

    /**
     * @param $newName
     */
    public function renameFile($newName)
    {
        if (!rename($this->filePath, $newPath = $this->getReformFilePath($newName))) {
            dir("Fail rename $this->filePath, to $newPath");
        }
        $this->filePath = $newPath;
        $this->storeRename();
        $this->storeSerialName($newName);
    }

    /**
     * File constructor.
     *
     * @param $filePath
     * @throws \Exception
     */
    public function __construct(EngineInterface $engine, $filePath)
    {
        $this->engine = $engine;
        $this->filePath = $filePath;
        if (!file_exists($filePath)) {
            throw new \Exception("File $filePath not found");
        }
    }
}