<?php
require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\InifileParser;
use wcmf\lib\core\Log;

$GLOBALS['CONFIG_PATH'] = WCMF_BASE.'test/config/';
Log::configure($GLOBALS['CONFIG_PATH'].'log4php.properties');

$configFile = $GLOBALS['CONFIG_PATH'].'config.ini';
$parser = InifileParser::getInstance();
$parser->parseIniFile($configFile, true);
?>
