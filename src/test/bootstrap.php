<?php
require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;

$configPath = WCMF_BASE.'app/config/';

Log::configure('log4php.properties');
$config = new InifileConfiguration($configPath);
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);
?>
