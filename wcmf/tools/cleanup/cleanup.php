<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>wCMF Cleanup Tool</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="author" content="wemove digital solutions" />
<meta name="copyright" content="wemove digital solutions GmbH" />
<link rel="StyleSheet" href="../../../wcmf/blank/style/style.css" type="text/css">
<script type="text/javascript">
function check(state)
{
  var f = document.forms.files;
  for (var i = 0; i < f.elements.length; i++)
    if (f.elements[i].name == "removeFiles[]")
      f.elements[i].checked = state;
}
</script>
</head>

<body>

<?php
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\io\FileUtil;
use wcmf\lib\model\PersistentIterator;
use wcmf\lib\util\StringUtil;

$action = $_POST["action"];
$filesToRemove = $_POST["removeFiles"];

$config = new InifileConfiguration('./');
$config->addConfiguration('config.ini');

// media directory
$mediaDir = $config->getValue('mediaDir', 'cleanupdir');
if ($mediaDir === false)
{
  Log::error("No value for 'uploadDir' in the config file's section 'cleanupdir' provided.", 'cleanup');
  exit();
}
$relMediaDir = $config->getValue('relMediaDir', 'cleanupdir');

// get all media files defined in the database
$mediaFilesDB = array();
// iterate over rootTypes
$rootTypes = $config->getValue('rootTypes', 'application');
if (is_array($rootTypes))
{
  $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
  foreach($rootTypes as $rootType)
  {
    $rootOIDs = array_merge($oids, $persistenceFacade->getOIDs($rootType));
    foreach($rootOIDs as $rootOID)
    {
      $iter = new PersistentIterator($rootOID);
      foreach($iter as $depth => $oid)
      {
        $node = $persistenceFacade->load($oid, BuildDepth::SINGLE);

        $valueNames = $node->getValueNames();
        foreach($valueNames as $valueName)
        {
          $valueProperties = $node->getValueProperties($valueName);
          $value = $node->getValue($valueName);
          // find all uploaded files
          if (strpos($valueProperties['input_type'], 'file') === 0)
          {
            if (strlen($value) > 0)
            {
              if (!is_array($mediaFilesDB[$value])) {
                $mediaFilesDB[$value] = array();
              }
              if (!in_array($node->getOID(), $mediaFilesDB[$value])) {
                array_push($mediaFilesDB[$value], $node->getOID());
              }
            }
          }
          // find links to media files embedded in textfields etc.
          $embeddedURLs = StringUtil::getUrls($value);
          if (sizeOf($embeddedURLs) > 0)
          {
            foreach($embeddedURLs as $url)
            {
              if (strpos($url, 'http://') === 0 && strpos($url, 'https://') === 0 && strpos($url, $mediaDir) === 0)
              {
                if (!is_array($mediaFilesDB[basename($url)])) {
                  $mediaFilesDB[basename($url)] = array();
                }
                if (!in_array($node->getOID(), $mediaFilesDB[basename($url)])) {
                  array_push($mediaFilesDB[basename($url)], $node->getOID());
                }
              }
            }
          }
        }
      }
    }
  }
}
ksort($mediaFilesDB);

// compare with media files in media directory
$mediaFiles = FileUtil::getFiles($relMediaDir);

if (is_array($mediaFilesDB) && is_array($mediaFiles))
{
  $UniqueMediaFiles = array_diff(array_keys($mediaFilesDB), $mediaFiles);
  $UniqueMediaFilesDB = array_diff($mediaFiles, array_keys($mediaFilesDB));
}
else
{
  $UniqueMediaFiles = array();
  $UniqueMediaFilesDB = array();
}

if ($action == "removeFiles" && is_array($filesToRemove) > 0)
{
  foreach($UniqueMediaFilesDB as $filename)
  {
    if (in_array($filename, $filesToRemove))
    {
      unlink($relMediaDir.$filename);
      array_shift($UniqueMediaFilesDB);
    }
  }
}

// output

if (sizeOf($UniqueMediaFiles) > 0)
{
  echo "<span class='error'>Warning! The following files are referenced in the database<br />";
  echo "but don't exist in the media directory - they might be missing<br />";
  echo "on the website:</span><br /><br />";
  foreach($UniqueMediaFiles as $key=>$value)
  {
    echo $value." [";
    $i = 0;
    foreach($mediaFilesDB[$value] as $filename)
    {
      $i++;
      echo $filename;
      if ($i < sizeOf($mediaFilesDB[$value]))
        echo ", ";
    }
    echo "]<br />";
  }
  echo "<br /><br />";
}
else
  echo "All files referenced in the database exist in the media directory.<br />";

if (sizeOf($UniqueMediaFilesDB) > 0)
{
  echo "The following files from the upload directory are not referenced in the database<br />";
  echo "- if you don't use them for other purposes they can be removed:<br /><br />";
  echo "<a href='javascript:check(1);'>select all files</a> | <a href='javascript:check(0);'>deselect all files</a>";
  echo "<br /><br />";
  echo "<form action='cleanup.php' method='post' name='files'>";
  echo "<input type='hidden' name='action' value='removeFiles'>";
  foreach($UniqueMediaFilesDB as $filename)
    echo "<input type='checkbox' name='removeFiles[]' value='".$filename."' class='check'> ".$filename."<br />";
  echo "<br />";
  echo "<input type='submit' value='Remove selected files' class='formbutton'>";
  echo "</form>";
}
else
  echo "All files in the media directory are referenced in the database.<br />";
?>
</body>
</html>

