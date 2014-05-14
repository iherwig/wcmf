<?php
/**
 * This script converts a word into a wCMF password
 */
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.php");

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\config\impl\InifileConfiguration;

// read config file
$config = new InifileConfiguration('../../../app/config/');
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);
?>
<html>
<body>
<?php
  echo("password to hash:");
?>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
  <input name="password" type="password" />
  <input type="submit" />
</form>
<?php
  if (array_key_exists("password", $_POST))
	  echo("hashed password: ".ObjectFactory::getInstance('User')->hashPassword($_POST["password"]));
?>
</body>
</html>