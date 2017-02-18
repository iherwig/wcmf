<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
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

  private $baseDir = '';

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
    $this->baseDir = $baseDir;
    spl_autoload_register([$this, 'load'], true, true);
  }

  /**
   * Load the given class definition
   * @param $className The class name
   */
  private function load($className) {
    // search under baseDir assuming that namespaces match directories
    $filename = $this->baseDir.str_replace("\\", "/", $className).'.php';
    if (file_exists($filename)) {
      include($filename);
    }
  }
}
?>
