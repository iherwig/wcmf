<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
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
 *
 * // key/value pairs provided by static method getValues in class ListValueProvider
 * {"type":"function","name":"name\\\\space\\\\ListValueProvider::getValues"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FunctionListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getList
   * $options is an associative array with keys 'name' and 'params' (optional)
   */
  public function getList($options, $valuePattern=null, $key=null, $language=null) {
    if (!isset($options['name'])) {
      throw new ConfigurationException("No 'name' given in list options: "+StringUtil::getDump($options));
    }
    $name = $options['name'];
    $params = isset($options['params']) ? $options['params'] : null;

    $nameParts = explode('::', $options['name']);
    if (count($nameParts) == 1 && function_exists($nameParts[0])) {
      $functionName = $nameParts[0];
      $map = call_user_func_array($functionName, $params === null ? [] : $params);
    }
    elseif (count($nameParts) == 2 && method_exists($nameParts[0], $nameParts[1])) {
      $className = $nameParts[0];
      $methodName = $nameParts[1];
      $map = $params === null ? $className::$methodName() : $className::$methodName(...$params);
    }
    else {
      throw new ConfigurationException('Function or method '.$name.' is not defined!');
    }

    // translate values
    $result = [];
    $message = ObjectFactory::getInstance('message');
    foreach ($map as $curKey => $curValue) {
      $displayValue = $message->getText($curValue, null, $language);
      if ((!$valuePattern || preg_match($valuePattern, $displayValue)) && (!$key || $key == $curKey)) {
        $result[$curKey] = $displayValue;
      }
    }
    return $result;
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($options) {
    return false;
  }
}
?>
