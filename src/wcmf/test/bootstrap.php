<?php
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(dirname(WCMF_BASE))."/wcmf/vendor/autoload.php");

use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\ClassLoader;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;

new ClassLoader();

ob_start();
$configPath = WCMF_BASE.'wcmf/test/app/config/';

Log::configure('log4php.php');
$config = new InifileConfiguration($configPath);
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);
ob_clean();
?>
