<?php
/**
 * This script demonstrates the use of adodb performance monitor
 */
error_reporting(E_ERROR | E_PARSE);
define("BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

// @note: set section [cms] logSQL = 1

require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/3rdparty/adodb/adodb.inc.php");

session_start(); # session variables required for monitoring

$parser = &InifileParser::getInstance();
$parser->parseIniFile('config.ini', true);

$params = $parser->getSection("database");
$conn = ADONewConnection($params["dbType"]);
$conn->Connect($params["dbHostName"], $params["dbUserName"], $params["dbPassword"], $params["dbName"]);
$perf =& NewPerfMonitor($conn);
$perf->UI($pollsecs=5);
?>

