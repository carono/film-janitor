<?php

namespace carono\janitor;

use carono\janitor\Cli as JanitorCli;
use carono\janitor\helpers\Console;
use carono\janitor\helpers\FileHelper;
use carono\janitor\helpers\Inflector;
use Dotenv\Dotenv;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class JanitorCommand extends CLI
{
    const CMD_FIXTURE = 'fixture';
    const CMD_CLEAR_CACHE = 'clear-cache';
    const CMD_SET_ENGINE_OPTIONS = 'set-engine-options';
    const CMD_SET_OPTION = 'set-option';
    const CMD_RESET_ENV = 'reset-env';

    protected static $env = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env';

    public function __construct($autocatch = true)
    {
        parent::__construct($autocatch);

        if (!file_exists(static::$env)) {
            $this->cmdResetEnv(new Options);
        }
        $dotenv = Dotenv::create(dirname(static::$env));
        $dotenv->load();
        $dotenv->required(['SEARCH_ENGINE']);
    }

    protected function setup(Options $options)
    {
        $options->setHelp('');
        $options->registerOption('directory', 'Directory for refactor films', 'd', true);

        $options->registerCommand(static::CMD_FIXTURE, 'Create video files for testing');
        $options->registerCommand(static::CMD_CLEAR_CACHE, 'Clearing cache');
        $options->registerCommand(static::CMD_SET_ENGINE_OPTIONS, 'Set engine options');
        $options->registerCommand(static::CMD_RESET_ENV, 'Reset env options');
    }

    protected function setEnvOption($option, $value)
    {
        $value = addcslashes($value, '"\\');
        $env = static::$env;
        $envContent = file_get_contents($env);
        $option = preg_quote($option, '/');
        $data = $option . '="' . $value . '"';
        $newEnvContent = preg_replace("/^ ?$option ?=.+$/m", str_replace('\\', '\\\\', $data), $envContent, -1, $c);
        if (!$c) {
            $data = $envContent ? "\n" . $data : $data;
            file_put_contents($env, $data, FILE_APPEND);
        } else {
            file_put_contents($env, $newEnvContent);
        }
    }

    public function cmdRefactoring($dir)
    {
        foreach (JanitorCli::getEngine()->getRequiredEnvironmentOptions() as $option => $description) {
            if (!getenv($option)) {
                $value = Console::prompt($description);
                $this->setEnvOption($option, $value);
            }
        }
        $realPath = realpath($dir);
        if (!dir($realPath)) {
            die("Dir $dir not found\n");
        }
        if (Console::confirm('Refactor ' . realpath($dir) . '?')) {
            JanitorCli::reform($dir);
        }
    }

    /**
     * @param Options $options
     */
    public function cmdFixture(Options $options)
    {
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
    }

    /**
     * @param Options $options
     */
    public function cmdClearCache(Options $options)
    {
        if (file_exists(\carono\janitor\Cli::$cacheFile)) {
            FileHelper::unlink(\carono\janitor\Cli::$cacheFile);
        }
        if (file_exists(\carono\janitor\Cli::$renamedFile)) {
            FileHelper::unlink(\carono\janitor\Cli::$renamedFile);
        }
        echo "Clearing\n";
    }

    /**
     * @param Options $options
     */
    public function cmdSetEngineOptions(Options $options)
    {
        foreach (JanitorCli::getEngine()->getRequiredEnvironmentOptions() as $option => $description) {
            $value = getenv($option);
            $value = Console::prompt($description, ['default' => $value]);
            $this->setEnvOption($option, $value);
        }
    }

    /**
     * @param Options $options
     */
    public function cmdResetEnv(Options $options)
    {
        if (Console::confirm('Reset env to default')) {
            copy(static::$env . '.example', static::$env);
            Console::output('Env reverted to default');
        }
    }

    /**
     * @param Options $options
     */
    protected function main(Options $options)
    {
        if ($dir = $options->getOpt('directory')) {
            $this->cmdRefactoring($dir);
            exit;
        }

        $cmd = $options->getCmd();
        $method = 'cmd' . Inflector::camelize($cmd);
        if ($cmd && method_exists($this, $method)) {
            $this->$method($options);
            exit;
        }

        echo $options->help();
    }
}