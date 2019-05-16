<?php

namespace carono\janitor;

use carono\janitor\engines\EngineAbstract;
use carono\janitor\helpers\Console;
use carono\janitor\helpers\FileHelper;

/**
 * Class Cli
 *
 * @package carono\janitor
 */
class Cli
{
    public static $renamedFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'renamed.json';
    public static $cacheFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache.json';
    public static $temporaryDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp';

    protected static function getFileTypeMessage(File $file)
    {
        $message = 'File type: ';
        if ($file->isFilm()) {
            $message .= 'Movie';
        } else {
            $message .= 'Serial, Season ' . $file->getSeasonNumber() . ' Episode ' . $file->getEpisodeNumber();
        }
        return $message;
    }

    protected static function out($text = '', $newLine = true)
    {
        echo $text . ($newLine ? "\n" : '');
    }

    /**
     * @param $dir
     */
    public static function reformFiles($dir)
    {
        /**
         * @var File $file
         */
        $files = static::getFiles($dir);
        $renameAll = false;
        $fileModels = array_map(function ($path) {
            return new File(static::getEngine(), $path);
        }, $files);
        $i = 0;
        $selectedName = null;
        $request = null;
        while ($file = array_shift($fileModels)) {
            $value = null;
            if ($file->wasRenamed()) {
                echo "{$file->getFilePath()} SKIP\n";
                continue;
            }

            $options = [
                'r' => 'Confirm rename',
                's' => 'Skip file',
                'c' => 'Custom name for search',
                'n' => 'Next title',
                'i' => 'Ignore this file'
            ];

            if ($file->isSerial()) {
                $options['a'] = 'All rename for serial';
            }

            $selectedName = $file->getStoredSerialName();

            if (!$renameAll || !$file->getStoredSerialName()) {
                $renameAll = false;
                $names = $file->searchFilmNames($request);
                Console::clearScreen();

                if ($file->getStoredSerialName()) {
                    $file->restoreSerialData();
                    if (($i = array_search($selectedName, $names)) === false) {
                        $i = [];
                    }
                } else {
                    $selectedName = $names[$i];
                }

                $reformedFileName = $file->getReformFileName($selectedName);

                Console::selectBox('Select correct title:', $names, $i);
                self::out();
                self::out("Original file: {$file->getFileName()}");
                self::out("New file name: {$reformedFileName}");
                if ($file->isSerial()) {
                    self::out('Files in directory: ' . count(FileHelper::findFiles($file->getFolder())));
                }
                self::out();
                $value = Console::select('What do?', $options);

            } else {
                echo $file->getFilePath() . ' => ' . $file->getReformFilmName($selectedName) . ": Renamed\n";
            }

            if ($value === 'i') {
                $file->storeRename();
                continue;
            }

            if ($value === 'n') {
                $i++;
                if (!isset($names[$i])) {
                    $i = 0;
                }
            }

            if ($value === 'a') {
                $renameAll = true;
            }

            if ($value === 'r' || $value === 'a' || $renameAll) {
                $file->renameFile($selectedName);
                $i = 0;
                continue;
            }

            if ($value === 'c') {
                $request = Console::prompt('Write film name for searching:');
            }

            array_unshift($fileModels, $file);
        }
    }

    /**
     * @param $dir
     */
    public static function reform($dir)
    {
        static::reformFiles(realpath($dir));
    }

    /**
     * @param $name
     * @param $season
     * @return string
     */
    public static function getSerialFolderName($name, $season)
    {
        $name = pathinfo($name, PATHINFO_FILENAME);
        return $name . ' сезон ' . $season;
    }

    /**
     * @param $dir
     * @return array
     */
    protected static function getFiles($dir)
    {
        return FileHelper::findFiles($dir);
    }

    /**
     * @param $name
     * @return bool
     */
    public static function extractYear($name)
    {
        if (preg_match_all('/(\d{4})/', $name, $m)) {
            foreach ($m[0] as $year) {
                if ($year > 1900 && $year <= date('Y')) {
                    return $year;
                }
            }
        }
        return false;
    }

    /**
     * @param null $season
     * @param null $episode
     * @return string
     */
    public static function getEpisodeName($season = null, $episode = null)
    {
        $season = str_pad($season, 2, '0', STR_PAD_LEFT);
        $episode = str_pad($episode, 2, '0', STR_PAD_LEFT);
        return "s{$season}e{$episode}";
    }

    /**
     * @return EngineAbstract
     */
    public static function getEngine()
    {
        $class = getenv('SEARCH_ENGINE');
        if (!class_exists($class)) {
            die("Class $class not found\n");
        }
        return new $class;
    }
}