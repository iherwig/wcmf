<?php
/**
 * This script extracts class dependencies by searching for
 * - new Constructor()
 * - Static::method()
 * - extends Superclass {
 * - implements Superclass {
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
      // collect references first for later sorting
      $usages = array();
      foreach ($dependencies as $dependency) {
        $namespaces = searchClass($dependency);
        $postfix = sizeof($namespaces) > 1 ? ' // ambiguous' : '';
        foreach ($namespaces as $ns) {
          $usages[] = $ns.";".$postfix;
        }
      }
      sort($usages);
      foreach ($usages as $usage) {
        echo "use ".$usage."<br>\n";
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

    preg_match_all(
            '/\s+new\s+(\w+?)\s*\(|'.    // new Constructor()
            '\s+(\w+?)::.*?\s*\(|'.      // Static::method()
            'extends\s+(\w+?)\s*\{|'.    // extends Superclass {
            'implements\s+(\w+?)\s*\{|'. // implements Superclass {
            '[\(,]\s*(\w+)\s+/i'         // (Typehint1 param1, Typehint2 param2
            , $content, $matchesTmp);
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
    // php base classes in the global namespace
    if (in_array($name, array('Exception'))) {
      $namespaces = array('\\'.$name);
    }
    else {
      // search in directories
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
    }
    $foundNamespaces[$name] = $namespaces;
  }
  return $foundNamespaces[$name];
}
?>