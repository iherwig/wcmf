<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\control\lists\ListStrategy;
use wcmf\lib\util\StringUtil;

/**
 * FileListStrategy implements a list of key value pairs that is retrieved
 * from an configuration section.
 *
 * Configuration examples:
 * @code
 * // .ini files
 * {"type":"file","paths":["path/to/files"],"pattern":"\\\\.ini$"}
 *
 * // all files recursive
 * {"type":"file","paths":["path/to/files/*"]}
 *
 * // multiple paths
 * {"type":"file","paths":["path/to/files","path/to/files2/*"]}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getList
   * $options is an associative array with keys 'paths' and 'pattern' (optional)
   */
  public function getList($options, $language=null) {
    if (!isset($options['paths']) || !is_array($options['paths'])) {
      throw new ConfigurationException("No array 'paths' given in list options: "+StringUtil::getDump($options));
    }
    $paths = $options['paths'];
    $pattern = isset($options['pattern']) ? '/'.$options['pattern'].'/' : '/./';

    $fileUtil = new FileUtil();
    $list = array();
    foreach ($paths as $path) {
      $recursive = preg_match('/\/\*$/', $path);
      if ($recursive) {
        $path = preg_replace('/\*$/', '', $path);
      }
      // if multiple directories or recursive, we show the complete file path
      $prependDirectory = sizeof($paths) > 1 || $recursive;

      $files = $fileUtil->getFiles($path, $pattern, $prependDirectory, $recursive);
      foreach ($files as $file) {
        $list[$file] = $file;
      }
    }
    return $list;
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($options) {
    return false;
  }
}
?>
