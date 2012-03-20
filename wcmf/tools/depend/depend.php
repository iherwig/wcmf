<?php
/**
 * This script extracts class dependencies by searching for
 * - new Constructor()
 * - Static::method()
 * - extends Superclass {
 * - (Typehint1 param1, Typehint2 param2
 */
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\io\FileUtil;

getDependencies();

function getDependencies() {
  $exclude = '/\/3rdparty\/|\/templates_c\//';
  $fileUtil = new FileUtil();
  $files = $fileUtil->getFiles(WCMF_BASE, '/\.php$/', true, true);
  foreach ($files as $file) {
    if (!preg_match($exclude, $file)) {
      $dependencies = getDependenciesFromFile($file);
      echo "<br>\n".$file."<br>\n";
      foreach ($dependencies as $dependency) {
        $namespaces = searchClass($dependency);
        $prefix = sizeof($namespaces) > 1 ? '? ' : '';
        foreach ($namespaces as $ns) {
          echo $prefix."use ".$ns."<br>\n";
        }
      }
    }
  }
}

function getDependenciesFromFile($file) {
  $result = array();
  if (file_exists($file)) {
    $fh = fopen($file, "r");
    $content = fread($fh, filesize ($file));
    fclose($fh);

    preg_match_all('/\s+new\s+(\w+?)\(\)|\s+(\w+?)::.*?\(|extends\s+(\w+?)\s*\{|[\(,]\s*(\w+)\s+/i', $content, $matchesTmp);
    $matches = array();
    $excludes = array('', 'parent', 'self');
    foreach(array_merge($matchesTmp[1], $matchesTmp[2], $matchesTmp[3], $matchesTmp[4]) as $match) {
      if (!in_array($match, $excludes) && !in_array($match, $matches)) {
        $matches[] = $match;
      }
    }
    if (sizeof($matches) > 0) {
      $result = $matches;
    }
  }
  return $result;
}

function searchClass($name) {
  static $foundNamespaces = array();
  if (!isset($foundNamespaces[$name])) {
    $namespaces = array();
    $fileUtil = new FileUtil();
    $files = $fileUtil->getFiles(WCMF_BASE, '/\.php$/', true, true);
    foreach ($files as $file) {
      if (preg_match('/\/'.$name.'\.php$/', $file)) {
        $usage = str_replace(WCMF_BASE, '', $file);
        $usage = str_replace('.php', '', $usage);
        $usage = str_replace('/', '\\', $usage);
        $namespaces[] = $usage;
      }
    }
    $foundNamespaces[$name] = $namespaces;
  }
  return $foundNamespaces[$name];
}
?>