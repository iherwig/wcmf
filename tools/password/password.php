<?php
/**
 * This script converts a word into a wCMF password
 */
error_reporting(E_ERROR | E_PARSE);
define("BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/security/class.UserManager.php");  

?>
<html>
<body>
<?php
  echo("password to encrypt:");
?>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
  <input name="password" type="password" />
  <input type="submit" />
</form>
<?php
  if (array_key_exists("password", $_POST ))
	  echo("encypted password: ".UserManager::encryptPassword($_POST["password"]));
?>
</body>
</html>