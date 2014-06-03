<?php
require_once("base_dir.php");
require_once(dirname(dirname(WCMF_BASE))."/wcmf/vendor/autoload.php");

use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\ClassLoader;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;

ob_start();
$configPath = WCMF_BASE.'wcmf/test/app/config/';

new ClassLoader();
Log::configure('log4php.php');
$config = new InifileConfiguration($configPath);
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);
ob_clean();
?>