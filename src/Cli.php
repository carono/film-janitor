<?php

namespace carono\janitor;

use carono\janitor\engines\EngineInterface;
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

    /**
     * @param $dir
     */
    public static function reformFiles($dir)
    {
        /**
         * @var File $file
         */
        $files = static::getFiles($dir);
        $customName = null;
        $renameAll = false;
        $file = null;
        $fileModels = array_map(function ($path) {
            return new File($path);
        }, $files);
        while ($file = array_shift($fileModels)) {
            $value = null;
            $file->customName = $customName;
            $reformedFileName = $file->getReformedFileName();
            $fileName = $file->getFileName();

            if ($file->wasRenamed()) {
                echo "{$file->filePath} SKIP\n";
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

            if (!$renameAll || !$file->getStoredSerialName()) {
                Console::clearScreen();
                $renameAll = false;
                echo 'File type: ';
                if ($file->isFilm()) {
                    echo 'Movie';
                } else {
                    echo 'Serial, Season ' . $file->getSeasonNumber() . ' Episode ' . $file->getEpisodeNumber();
                }
                echo "\n";
                foreach ($file->formFilmNames($customName) as $i => $name) {
                    echo '[' . ($i === $file->indexName && !$file->getStoredSerialName() ? 'X' : ' ') . '] ' . $name . "\n";
                }
                echo "\n";
                echo "Original file: $fileName\n";
                echo "New file name: $reformedFileName\n";
                echo "\n";
                $value = Console::select('What do?', $options);
            } else {
                echo $fileName . ' => ' . $reformedFileName . ": Renamed\n";
            }

            if ($value === 'i') {
                $file->storeRename();
                continue;
            }

            if ($value === 'n') {
                $file->indexName++;
                $file->removeStoreSerialName();
                $file->searchFilmName($customName);
                if (!$file->reformedName) {
                    $file->indexName = 0;
                }
                array_unshift($fileModels, $file);
                continue;
            }

            if ($value === 'r' || $value === 'a' || $renameAll) {
                $file->renameFile();
                $file->storeSerialName();
            }

            if ($value === 'a') {
                $renameAll = true;
                array_unshift($fileModels, $file);
            }

            if ($value === 'c') {
                $customName = Console::prompt('Write film name for searching:');
                array_unshift($fileModels, $file);
                continue;
            }


            $customName = null;
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
     * @param $file
     * @param int $indexName
     * @return mixed|string
     */
    public static function getRealFilmNames($file)
    {
        $clearedName = static::clearName($file);
        return static::getRealNames($clearedName);
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
     * @return string
     */
    public static function clearName($name)
    {
        $replaceWithLast = [
            '720p',
            '1080p',
            'x264',
            'NewStudio',
            'Zuich32',
            'LostFilm',
            'WEBRip',
            'WEB-DL',
            'WEB',
            'BDRip',
            'HDRip',
            '\bD\b',
            '\bP\b',
            '\bTS\b',
            'Kuraj-Bambey',
            '\bRUS\b',
            'Jaskier'
        ];
        $replace = [
            '.' => ' ',
            '_' => ' '
        ];
        $name = pathinfo($name, PATHINFO_FILENAME);
        $name = trim(strtr($name, $replace));
        foreach ($replaceWithLast as $pattern) {
            $pattern = quotemeta($pattern);
            $name = preg_replace("/$pattern.+/ui", '', $name);
        }
        $name = preg_replace('/\ss\d+/', ' ', $name);
        $name = preg_replace('/e\d+/', ' ', $name);
        return trim($name);
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
     * @param $name
     * @return int|null
     */
    public static function getSeasonNumberFromName($name)
    {
        $patterns = [
            's(\d+)',
            'season.{1}(\d+)',
            'сезон.{1}(\d+)'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match("/$pattern/iu", $name, $match)) {
                return (int)$match[1];
            }
        }
        return null;
    }

    /**
     * @param $name
     * @return int|null
     */
    public static function getEpisodeNumberFromName($name)
    {
        $patterns = [
            'e(\d+)',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match("/$pattern/iu", $name, $match)) {
                return (int)$match[1];
            }
        }
        return null;
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
     * @return EngineInterface
     */
    public static function getEngine()
    {
        $class = getenv('SEARCH_ENGINE');
        if (!class_exists($class)) {
            die("Class $class not found\n");
        }
        return new $class;
    }

    /**
     * @param $request
     * @return array
     */
    public static function getRealNames($request)
    {
        $engine = static::getEngine();
        return $engine->getTitles($request);
    }
}