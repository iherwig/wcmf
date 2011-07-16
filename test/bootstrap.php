<?php
require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/util/Log.php");
require_once(WCMF_BASE."wcmf/lib/util/InifileParser.php");

Log::configure('log4php.properties');

// get configuration from file
$GLOBALS['CONFIG_PATH'] = WCMF_BASE . 'application/include/';
$configFile = $GLOBALS['CONFIG_PATH'] . 'config.ini';
$parser = InifileParser::getInstance();
$parser->parseIniFile($configFile, true);
?>
