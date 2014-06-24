<?php
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(WCMF_BASE)."/vendor/autoload.php");

use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\ClassLoader;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;

new ClassLoader();

ob_start();
$configPath = WCMF_BASE.'app/config/';
if (!file_exists($configPath)) {
  throw new Exception('Configuration path '.$configPath.' does not exist. '.
          'Did you forget to generate code from the model?');
}

// copy resources
FileUtil::copyRecDir('resources/app/', WCMF_BASE.'app/');

Log::configure('log4php.php');
$config = new InifileConfiguration($configPath);
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);
ob_clean();
?>
