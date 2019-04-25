<?php

use carono\janitor\JanitorCommand;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';


$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();
$dotenv->required(['SEARCH_ENGINE']);

(new JanitorCommand())->run();