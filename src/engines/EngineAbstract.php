<?php


namespace carono\janitor\engines;


use carono\janitor\Cli;
use carono\janitor\File;
use carono\janitor\helpers\FileHelper;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

abstract class EngineAbstract implements EngineInterface
{
    protected static $cacheClass = FilesystemAdapter::class;
    protected static $cache;

    public function getOptions()
    {
        return [
            'ADD_YEAR' => 'Add year to movie, like "movie (2019).avi":1'
        ];
    }

    /**
     * @return \Symfony\Component\Cache\Adapter\AbstractAdapter
     */
    public static function getCache()
    {
        if (static::$cache) {
            return static::$cache;
        }
        $cache = new static::$cacheClass(static::getShortName(), 3600, Cli::$temporaryDir);
        static::$cache = $cache;

        return $cache;
    }

    /**
     * @param $name
     * @return null
     */
    public function getOptionDefaultValue($name)
    {
        if (isset($this->getOptions()[$name])) {
            return explode(':', $this->getOptions()[$name])[1];
        }
        return null;
    }

    /**
     * @param $name
     * @return null
     */
    public function getOptionDescription($name)
    {
        if (isset($this->getOptions()[$name])) {
            return explode(':', $this->getOptions()[$name])[0];
        }
        return null;
    }

    /**
     * @param $name
     * @return array|false|null|string
     */
    public function getOption($name)
    {
        $key = 'ENGINE_OPTION_' . $name;
        if (($value = getenv($key)) === null) {
            return $this->getOptionDefaultValue($name);
        }
        return $value;
    }

    /**
     * @return string
     */
    protected static function getShortName()
    {
        $reflection = new \ReflectionClass(static::class);
        return $reflection->getShortName();
    }

    /**
     * @return bool|string
     */
    protected static function getTemporaryDir()
    {
        $dir = Cli::$temporaryDir . DIRECTORY_SEPARATOR . static::getShortName();
        FileHelper::createDirectory($dir);
        return realpath($dir);
    }

    /**
     * @param $request
     * @param $data
     */
    public static function setCacheValue($request, $data)
    {
        $item = static::getCache()->getItem(md5($request));
        $item->set($data);
        static::getCache()->save($item);
    }

    /**
     * @param $request
     * @return mixed
     */
    public static function getCacheValue($request)
    {
        return static::getCache()->getItem(md5($request))->get();
    }

    public function clearCache()
    {
        FileHelper::removeDirectory(static::getTemporaryDir());
    }

    /**
     * @param $text
     * @return int|null
     */
    public function parseSerialSeason($text)
    {
        $patterns = [
            's(\d+)',
            'season.{1}(\d+)',
            'сезон.{1}(\d+)'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match("/$pattern/iu", $text, $match)) {
                return (int)$match[1];
            }
        }
        return null;
    }

    /**
     * @param $text
     * @return int|null
     */
    public function parseSerialEpisodeNumber($text)
    {
        $patterns = [
            'e(\d+)',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match("/$pattern/iu", $text, $match)) {
                return (int)$match[1];
            }
        }
        return null;
    }

    /**
     * @param File $file
     * @param string $newName
     * @param string $lastRequest
     * @return string
     */
    public function reformFilmName(File $file, $newName, $lastRequest)
    {
        $name = $newName;
        if ($file->isSerial()) {
            $name .= ' ' . $file->getSuffix();
        }
        if ($this->getOption('ADD_YEAR')) {
            $years = [];
            $years[] = Cli::extractYear($newName);
            $years[] = Cli::extractYear($lastRequest);
            $years[] = Cli::extractYear(FileHelper::prepareFileName($file->getFilePath()));
            if ($year = current(array_filter($years))) {
                $name .= " ($year)";
            }
        }

        return $name;
    }
}