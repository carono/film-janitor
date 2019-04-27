<?php


namespace carono\janitor\engines;


use carono\janitor\Cli;
use carono\janitor\helpers\FileHelper;

abstract class EngineAbstract implements EngineInterface
{

    protected static function getTemporaryDir()
    {
        $reflection = new \ReflectionClass(static::class);
        $dir = Cli::$temporaryDir . DIRECTORY_SEPARATOR . $reflection->getShortName();
        FileHelper::createDirectory($dir);
        return realpath($dir);
    }

    /**
     * @param $request
     * @param $data
     */
    public static function storeCache($request, $data)
    {
        $md5 = md5($request);
        $file = static::getTemporaryDir() . DIRECTORY_SEPARATOR . $md5 . '.bin';
        file_put_contents($file, $data);
    }

    /**
     * @param $request
     * @return string
     */
    public static function getCache($request)
    {
        $md5 = md5($request);
        $file = static::getTemporaryDir() . DIRECTORY_SEPARATOR . $md5 . '.bin';
        if (file_exists($file)) {
            return (string)file_get_contents($file);
        }
        return '';
    }

    public function clearCache()
    {
        FileHelper::removeDirectory(static::getTemporaryDir());
    }
}