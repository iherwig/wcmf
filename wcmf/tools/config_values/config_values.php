<?php
/**
 * This script tries to find all configuration values referenced in the wCMF library
 * by searching for parser method calls like parser->getValue
 */
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\core\Log;

// collect values
$configValues = getConfigValues(WCMF_BASE."wcmf/", "/\.php$|\.tpl$/");

// known sections
$sections = array
(
  'config' => array(
        'include' => array('class.InifileParser.php'),
        'hiddenSections' => array('class.InifileParser.php'),
        'readonlySections' => array('class.InifileParser.php')
        ),
  'classmapping' => array(),
  'typemapping' => array(),
  'implementation' => array(),
  'initparams' => array(),
  'converter' => array(),
  'actionmapping' => array(),
  'views' => array(),
  'authorization' => array(),
  'cms' => array(),
  'database' => array(
        'dbType' => array('class.LockManagerRDB.php', 'class.NodeToSingleTableMapper.php', 'class.UserManagerRDB.php', 'class.AuthUserRDB.php'),
        'dbHostName' => array('class.LockManagerRDB.php', 'class.NodeToSingleTableMapper.php', 'class.UserManagerRDB.php', 'class.AuthUserRDB.php'),
        'dbName' => array('class.LockManagerRDB.php', 'class.NodeToSingleTableMapper.php', 'class.UserManagerRDB.php', 'class.AuthUserRDB.php'),
        'dbUserName' => array('class.LockManagerRDB.php', 'class.NodeToSingleTableMapper.php', 'class.UserManagerRDB.php', 'class.AuthUserRDB.php'),
        'dbPassword' => array('class.LockManagerRDB.php', 'class.NodeToSingleTableMapper.php', 'class.UserManagerRDB.php', 'class.AuthUserRDB.php')
        ),
  'htmlform' => array(),
  'smarty' => array(),
  'media' => array()
);
$entryLength = array(
  'config' => 16,
  'database' => 10,
  'htmlform' => 11
);

// resort into section array
foreach($configValues as $file => $entries)
{
  foreach($entries as $entry)
  {
    $section = $entry["section"];
    $option = $entry["option"];
    if (!is_array($sections[$section]))
      $sections[$section] = array();

    if (!is_array($sections[$section][$option]))
      $sections[$section][$option] = array();

    array_push($sections[$section][$option], $file);

    if (strlen($option) > $entryLength[$section])
      $entryLength[$section] = strlen($option);
  }
}

// print result
echo("<pre>");
foreach($sections as $section => $entries)
{
  echo("[".$section."]\n");
  foreach($entries as $option => $files)
  {
    $fillStr = str_repeat(" ", $entryLength[$section]-strlen($option));
    echo($option.$fillStr." = ; files: ".join(", ", $files)."\n");
  }
  echo("\n\n");
}
echo("</pre>");


// HELPER FUNCTIONS
function getConfigValues($directory, $pattern)
{
  static $result = array();

  if (substr($directory, -1) != '/')
    $directory .= '/';
  if (is_dir($directory))
  {
    $d = dir($directory);
    $d->rewind();
    while(false !== ($file = $d->read()))
      if($file != '.' && $file != '..')
      {
        if (is_dir($directory.$file))
          getConfigValues($directory.$file, $pattern);
        elseif (preg_match($pattern, $file))
        {
          Log::debug("Parsing: ".$directory.$file, 'config_values');
          $messages = getConfigValuesFromFile($directory.$file);
          if (sizeof($messages) > 0)
            $result[$file] = $messages;
        }
      }
    $d->close();
  }
  else
    Log::error("The directory '".$directory."' does not exist.", 'config_values');
  return $result;
}
function getConfigValuesFromFile($file)
{
  $result = array();
  if (file_exists($file))
  {
    $fh = fopen($file, "r");
    $content = fread($fh, filesize ($file));
    fclose($fh);
    preg_match_all('/parser->getValue\(([\'"])(.*?)\\1,\s*([\'"])(.*?)\\3/i', $content, $matchesTmp);
    $matches = array();
    // filter out empty and duplicates
    for($i=0; $i<sizeOf($matchesTmp[0]); $i++)
      array_push($matches, array('section' => $matchesTmp[4][$i], 'option' => $matchesTmp[2][$i]));
    if (sizeof($matches) > 0)
      $result = $matches;
  }
  return $result;
}
?>
