<?php
/**
 * This script demonstrates how to produce a image from a text.
 * It may be used to test the server capabilities regarding image creation.
 */
error_reporting(E_ERROR | E_PARSE);
define("BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(BASE."wcmf/lib/util/class.Log.php");  
require_once(BASE."wcmf/lib/util/class.GraphicsUtil.php");

$util = new GraphicsUtil();

$success = $util->renderTextTTF("wCMF Cookbook", "MICHAEL.TTF", 24, "FFFFFF", "34AACD", "title.png", 580, 30, 10, 24);
if (!$success)
  Log::error($util->getErrorMsg(), "graphics");
?>
<html>
<head>
</head>
<body>
<img src="title.png" />
</body>
<html>
