<?php
require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Application;

$configPath = WCMF_BASE.'testapp/config/';

Log::configure($configPath.'log4php.properties');
$config = new InifileConfiguration();
$config->setConfigPath($configPath);
$config->parseIniFile('config.ini');
ObjectFactory::registerInstance('configuration', $config);

$application = Application::getInstance();
$application->initialize();
?>
