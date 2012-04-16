<?php
require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\InifileParser;
use wcmf\lib\core\Log;

Log::configure('log4php.properties');

// get configuration from file
$GLOBALS['CONFIG_PATH'] = WCMF_BASE.'test/config/';
$configFile = $GLOBALS['CONFIG_PATH'].'config.ini';
$parser = InifileParser::getInstance();
$parser->parseIniFile($configFile, true);
?>
