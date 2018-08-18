<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\presentation\control\lists\ListStrategy;
use wcmf\lib\util\StringUtil;

/**
 * FunctionListStrategy implements a list of key/value pairs that is retrieved
 * by a global function.
 *
 * Configuration examples:
 * @code
 * // key/value pairs provided by global function g_getListValues
 * {"type":"function","name":"g_getListValues"}
 *
 * // key/value pairs provided by global function g_getListValues with parameters
 * {"type":"function","name":"g_getListValues","params":["param1","param2"]}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FunctionListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getList
   * $options is an associative array with keys 'name' and 'params' (optional)
   */
  public function getList($options, $language=null) {
    if (!isset($options['name'])) {
      throw new ConfigurationException("No 'name' given in list options: "+StringUtil::getDump($options));
    }
    $name = $options['name'];
    $params = isset($options['params']) ? $options['params'] : null;

    if (function_exists($name)) {
      $map = call_user_func_array($name, $params);
    }
    else {
      throw new ConfigurationException('Function '.$name.' is not defined globally!');
    }
    return $map;
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($options) {
    return false;
  }
}
?>
