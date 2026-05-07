<?php
define('BASE_PATH', dirname(__DIR__));

// -- Load environment variables first (before any other require) --
require_once BASE_PATH . '/core/Env.php';
Env::load(BASE_PATH . '/.env');

date_default_timezone_set(Env::get('APP_TIMEZONE', 'Africa/Nairobi'));
session_start();

define('BASE_URL',  Env::get('APP_BASE_PATH', '/CBE_LMS'));
define('APP_URL',   Env::get('APP_URL',       'http://localhost'));
define('APP_DEBUG', Env::get('APP_DEBUG', 'false') === 'true');

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/Mailer.php';
require_once BASE_PATH . '/core/Router.php';

$router = new Router();
$router->dispatch();
