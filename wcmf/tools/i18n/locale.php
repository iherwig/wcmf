<?php
/**
 * This script extracts application messages from calls to Message::get
 */
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\I18nUtil;

// read config file
$config = new InifileConfiguration('./');
$config->addConfiguration('config.ini');

// get config values
$searchDir = getConfigValue("searchDir", "i18n", true);
$localeDir = getConfigValue("localeDir", "cms", true);
$languages = getConfigValue("languages", "i18n");

$i18nUtil = new I18nUtil();

// get messages from search directory
$allMessages = $i18nUtil->getMessages($searchDir."/", "/\.php$|\.tpl$/");

foreach ($languages as $language)
{
  // get translations from old array file (created with po2array.php), if existing
  $messageFile = $localeDir.$language."/LC_MESSAGES/messages_".$language.".php";
  if (file_exists($messageFile))
    require($messageFile); // require_once does not work here !!!

  // prepare message array
  $messages = array();
  foreach ($allMessages as $file => $fileMessages)
    foreach ($fileMessages as $message)
    {
      if (!isset($messages[$message]))
      {
        $messages[$message] = array();
        $messages[$message]['translation'] = ${"messages_$language"}[$message];
        $messages[$message]['files'] = $file;
      }
      else
        $messages[$message]['files'] .= ', '.$file;
    }
  $messages = natcaseksort($messages);
  Log::info($messages, "locale");

  $languageParts = preg_split('/_/', $language);
  $i18nUtil->createPOFile(getConfigValue("applicationTitle", "cms"),
    getConfigValue("editor", "i18n"),
    getConfigValue("email", "i18n"),
    $languageParts[0],
    $languageParts[1],
    getConfigValue("charset", "i18n"),
    "main", $messages);
}
exit;

function natcaseksort($array)
{
  // Like ksort but uses natural sort instead
  $keys = array_keys($array);
  natcasesort($keys);

  foreach ($keys as $k)
   $new_array[$k] = $array[$k];

  return $new_array;
}

function getConfigValue($key, $section, $isDirectory=false)
{
  $config = ObjectFactory::getConfigurationInstance();
  $value = $config->getValue($key, $section);

  // add slash
  if ($isDirectory && substr($value, -1) != '/')
    $value .= '/';

  return $value;
}
?>