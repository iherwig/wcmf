<?php
/**
 * This script moves a given file to another location and updates all references
 * in the database
 */
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\io\FileUtil;

// read config file
$config = new InifileConfiguration('./');
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);
Log::configure('../log4php.properties');

// get config values
$dbParams = $config->getSection("database", true);
$toolConfig = $config->getSection("move", true);
$locations = $config->getSection("locations", true);

$deleteEmptyFolders = ($toolConfig['deleteEmptyFolders'] == 1);
Log::info($deleteEmptyFolders, "move");

// process locations
foreach ($locations as $source => $target) {
  relocate($source, $target,
          $toolConfig['fileBasePath'],
          $toolConfig['dbBasePath'],
          $dbParams,
          $deleteEmptyFolders
  );
}
exit;

/**
 * Check if the given string denotes a directory (trailing /)
 * @param path String
 * @return Boolean
 */
function isDirName($path) {
  return preg_match('/\/$/', $path);
}

/**
 * Relocate the given source directory/file to the given target
 * directory/file in filesystem and database
 * @param source Path to the source directory/file relative to bsaePath
 * @param target Path to the target directory/file relative to bsaePath
 * @param fileBasePath Base path in filesystem
 * @param dbBasePath Base path in database
 * @param deleteEmptyFolders Boolean defining if empty source folders should be
 * deleted or not (default: false)
 */
function relocate($source, $target, $fileBasePath, $dbBasePath, $dbParams, $deleteEmptyFolders=false) {
  $fileUtil = new FileUtil();

  // collect files first
  $filesToMove = array();
  $sourcePath = $fileBasePath.$source;
  if (file_exists($sourcePath)) {
    if (is_dir($sourcePath)) {
      $files = $fileUtil->getFiles($sourcePath, '/./', true, true);
      foreach ($files as $file) {
        $fileWithoutBase = str_replace($fileBasePath, '', $file);
        $filesToMove[$fileWithoutBase] = $target;
      }
    }
    else if (is_file($sourcePath)) {
      $filesToMove[$source] = $target;
    }
  }
  else {
    Log::error($source." does not exist", "move");
  }

  // actually move
  foreach ($filesToMove as $source => $target) {
    // move file
    $newFile = moveFile($source, $target, $fileBasePath, $deleteEmptyFolders);
    if ($newFile !== null) {
      // replace database references
      replaceInDatabase($dbBasePath.urldecode($source), $dbBasePath.$newFile, $dbParams);
    }
    else {
      Log::error("Failed to move ".$source, "move");
    }
  }
}

/**
 * Move the given source file to the given target directory/file
 * @param source Path to the source file relative to basePath
 * @param target Path to the target directory/file relative to basePath
 * @param basePath Base path
 * @param dbParams Array with database connection parameters
 * @param deleteEmptyFolders Boolean defining if empty source folders should be
 * deleted or not (default: false)
 * @return The new location of the file or null, on failure
 */
function moveFile($source, $target, $basePath, $deleteEmptyFolders=false) {
  $result = null;
  $fileUtil = new FileUtil();

  if (isDirName($target)) {
    // target is a directory, so we have to add the source filename
    $target .= basename($source);
  }

  $sourceFile = $basePath.$source;
  $targetFile = $basePath.$target;
  Log::info("move file: ".$sourceFile." -> ".$targetFile, "move");

  // create the target directory if not existing
  $targetDir = dirname($targetFile);
  if (!is_dir($targetDir)) {
    $fileUtil->mkdirRec($targetDir);
  }

  // move the file
  if (copy($sourceFile, $targetFile)) {
    unlink($sourceFile);
    $result = $target;
  }

  // delete empty folder, if requested
  if ($deleteEmptyFolders) {
    $sourceDir = dirname($sourceFile);
    if (sizeof($fileUtil->getFiles($sourceDir, '/./', true, true)) == 0) {
      Log::info("delete directory: ".$sourceDir, "move");
      rmdir($sourceDir);
    }
  }
  return $result;
}

/**
 * Replace the given string in the datebase
 * @param dbParams Array with connection parameters
 * @param oldStr String to be replaced
 * @param newStr String to replace with
 */
function replaceInDatabase($oldStr, $newStr, $dbParams) {
  Log::info("replace in database: ".$oldStr." -> ".$newStr, "move");

  // Connect to database server
  mysql_connect(
          $dbParams['dbHostName'],
          $dbParams['dbUserName'],
          $dbParams['dbPassword']
  );

  // Select database
  mysql_select_db($dbParams['dbName']);

  // List all tables in database
  $sql = "SHOW TABLES FROM ".$dbParams['dbName'];
  $tables_result = mysql_query($sql);

  if (!$tables_result) {
    Log::error("Database error, could not list tables\nMySQL error: ".mysql_error(), "move");
    exit;
  }

  Log::debug("In these fields '$oldStr' have been replaced with '$newStr'", "move");
  while ($table = mysql_fetch_row($tables_result)) {
    Log::debug("Table: ".$table[0], "move");
    $fields_result = mysql_query("SHOW COLUMNS FROM ".$table[0]);
    if (!$fields_result) {
      Log::error("Could not run query: ".mysql_error(), "move");
      exit;
    }
    if (mysql_num_rows($fields_result) > 0) {
      while ($field = mysql_fetch_assoc($fields_result)) {
        if (stripos($field['Type'], "VARCHAR") !== false || stripos($field['Type'], "TEXT") !== false) {
          Log::debug("  ".$field['Field'], "move");
          $sql = "UPDATE ".$table[0]." SET ".$field['Field']." = replace(".$field['Field'].", '$oldStr', '$newStr')";
          mysql_query($sql);
        }
      }
    }
  }
  mysql_free_result($tables_result);
}
?>