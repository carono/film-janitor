<?php

use carono\janitor\JanitorCommand;

$autoload = [
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];
foreach ($autoload as $file) {
    if (file_exists($file)) {
        define('COMPOSER_INSTALL', $file);
        break;
    }
}

if (!defined('COMPOSER_INSTALL')) {
    fwrite(
        STDERR,
        'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL .
        '    composer install' . PHP_EOL . PHP_EOL .
        'You can learn all about Composer on https://getcomposer.org/.' . PHP_EOL
    );

    die(1);
}


require COMPOSER_INSTALL;

(new JanitorCommand())->run();