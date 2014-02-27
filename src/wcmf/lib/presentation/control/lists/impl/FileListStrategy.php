<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\presentation\control\lists\ListStrategy;
use wcmf\lib\io\FileUtil;

/**
 * FileListStrategy implements list of key value pairs that is retrieved
 * from an configuration section.
 * The following list definition(s) must be used in the input_type configuraton:
 * @code
 * file:path/to/files|/\..ini$/ // .ini files
 *
 * file:path/to/files/*|/./ // all files recursive
 *
 * file:path/to/files1,path/to/files2/* // multiple paths
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getList
   */
  public function getList($configuration, $language=null) {

    $listConfig = $this->parseConfiguration($configuration);

    $list = array();
    $directories = $listConfig['directories'];
    $pattern = $listConfig['pattern'];

    foreach ($directories as $directory) {
      $recursive = preg_match('/\/\*$/', $directory);
      if ($recursive) {
        $directory = preg_replace('/\*$/', '', $directory);
      }
      // if multiple directories or recursive, we show the complete file path
      $prependDirectory = sizeof($directories) > 1 || $recursive;

      $files = FileUtil::getFiles($directory, $pattern, $prependDirectory, $recursive);
      foreach ($files as $file) {
        $list[$file] = $file;
      }
    }

    return $list;
  }

  /**
   * Parse the given list configuration
   * @param configuration The configuration
   * @return Associative array with keys 'directories' and 'pattern'
   */
  protected function parseConfiguration($configuration) {
    if (strPos($configuration, '|')) {
      list($dirDef, $pattern) = explode('|', $configuration, 2);
    }
    else {
      $dirDef = $configuration;
      $pattern = '/./';
    }
    $directories = explode(',', $dirDef);

    return array(
      'directories' => $directories,
      'pattern' => $pattern
    );
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($configuration) {
    return false;
  }
}
?>
