<?php

define('ROOT_PATH', dirname(__DIR__) . '/');
define('LUKIMAN_ROOT_PATH', ROOT_PATH);
chdir(ROOT_PATH);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/app.php';

use Lukiman\AuthServer\Models\AppClient;