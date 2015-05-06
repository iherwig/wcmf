<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\core;

/**
 * ClassLoader tries to load missing class definitions.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ClassLoader {

  private $_baseDir = '';

  /**
   * Constructor.
   * @param $baseDir Base directory from which namespaces will be resolved
   *    (usually WCMF_BASE)
   */
  public function __construct($baseDir) {
    if (!file_exists($baseDir) || is_file($baseDir)) {
      throw new \Exception("Base dir '".$baseDir."' is not a directory.");
    }
    $baseDir = preg_replace('/\/\/$/', '/', $baseDir.'/');
    $this->_baseDir = $baseDir;
    spl_autoload_register(array($this, 'load'), true, true);
  }

  private function load($className) {
    // search under baseDir assuming that namespaces match directories
    $filename = $this->_baseDir.str_replace("\\", "/", $className).'.php';
    if (file_exists($filename)) {
      include($filename);
    }
  }

  /**
  * Search a class definition in any subfolder of baseDir
  * Code from: http://php.net/manual/en/language.oop5.autoload.php
  *
  * @param $className The name of the class
  * @param $sub The start directory (optional, default: '/')
  * @return The directory name
  */
  function searchClass($className, $sub="/") {
    if(file_exists($this->_baseDir.$sub.getFileName($className))) {
      return $this->_baseDir.$sub;
    }

    $dir = dir($this->_baseDir.$sub);
    while(false !== ($folder = $dir->read())) {
      if($folder != "." && $folder != "..") {
        if(is_dir($this->_baseDir.$sub.$folder)) {
          $subFolder = searchClass($className, $sub.$folder."/");
          if($subFolder) {
            return $subFolder;
          }
        }
      }
    }
    $dir->close();
    return false;
  }
}
?>
