<?php

use Mvc\App;
use Mvc\Database;

if (!file_exists(__DIR__ . '/Core/Config.php')) {
	die("Create Config.php in 'Core' directory");
}

require_once __DIR__ . '/Core/Config.php';
require_once __DIR__ . '/Core/Autoload.php';

$app = App::getInstance()->init("Anime");

$app->start();

if (Database::exist())
	@Database::getInstance()->close();
