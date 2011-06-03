<?php
/**
 * This script tests regular expressions
 */
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
?>
<html>
<body>
<form action="<?php echo $PHP_SELF ?>" method="post">
  Text<br /><textarea name="text" cols="50" rows="10"><?php echo htmlspecialchars(stripslashes($HTTP_POST_VARS['text'])); ?></textarea><br /><br />
  RegExp<br /><input name="regexp" type="text" value="<?php echo htmlspecialchars(stripslashes($HTTP_POST_VARS['regexp'])); ?>" size="50"/><br /><br />
  <input type="submit" />
</form>
<?php
  if (strlen($HTTP_POST_VARS["regexp"]) > 0)
  {
    preg_match_all(stripslashes($HTTP_POST_VARS["regexp"]), $HTTP_POST_VARS["text"], $matches);
    Log::info($matches, "regexp");
  }
?>
</body>
</html>