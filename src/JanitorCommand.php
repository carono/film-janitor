<?php

namespace carono\janitor;

use carono\janitor\helpers\FileHelper;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;
use carono\janitor\helpers\Console;

class JanitorCommand extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('A very minimal example that does nothing but print a version');
        $options->registerOption('directory', 'Directory for refactor films', 'd', true);
        $options->registerCommand('fixture', 'Create video files for testing');
        $options->registerCommand('clear-cache', 'Clearing cache');
    }

    protected function main(Options $options)
    {
        if ($dir = $options->getOpt('directory')) {
            $realPath = realpath($dir);
            if (!dir($realPath)) {
                die("Dir $dir not found\n");
            }
            if (Console::confirm('Refactor ' . realpath($dir) . '?')) {
                \carono\janitor\Cli::reform($dir);
            }
            exit;
        }

        if ($options->getCmd() === 'fixture') {
            $appDir = dirname(__DIR__);
            $prefix = '';
            $videoDir = $appDir . DIRECTORY_SEPARATOR . 'video';
            $files = explode("\n", trim(file_get_contents($appDir . DIRECTORY_SEPARATOR . 'fixture.txt')));
            FileHelper::removeDirectory($videoDir);
            if (!mkdir($videoDir) && !is_dir($videoDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', 'video'));
            }
            foreach ($files as $file) {
                $file = trim($file);
                $fileName = mb_substr($file, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8');
                $baseName = basename($fileName);
                $dir = $appDir . '\\video' . (strpos($fileName, '\\') ? '\\' . dirname($fileName) : '');
                if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
                }
                $fullFilePath = $dir . '\\' . $baseName;
                echo $fullFilePath . "\n";
                file_put_contents($fullFilePath, $baseName);
            }
            exit;
        }

        if ($options->getCmd() === 'clear-cache') {
            if (file_exists(\carono\janitor\Cli::$cacheFile)) {
                FileHelper::unlink(\carono\janitor\Cli::$cacheFile);
            }
            if (file_exists(\carono\janitor\Cli::$renamedFile)) {
                FileHelper::unlink(\carono\janitor\Cli::$renamedFile);
            }
            echo "Clearing\n";
            exit;
        }

        echo $options->help();
    }
}