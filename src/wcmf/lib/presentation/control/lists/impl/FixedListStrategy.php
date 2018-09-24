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
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\control\lists\ListStrategy;
use wcmf\lib\util\StringUtil;

/**
 * FixedListStrategy implements a constant list of key/value pairs.
 *
 * Configuration examples:
 * @code
 * // list with only values (the keys will be the same as the values)
 * {"type":"fix","items":["val1","val2"]}
 *
 * // list with explicit key/value pairs
 * {"type":"fix","items":{"key1":"val1","key2":"val2"}}
 *
 * // list with key/value pairs defined in a global variable
 * {"type":"fix","items":"$global_array_variable"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FixedListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getList
   * $options is an associative array with keys 'items'
   */
  public function getList($options, $valuePattern=null, $key=null, $language=null) {
    if (!isset($options['items'])) {
      throw new ConfigurationException("No 'items' given in list options: "+StringUtil::getDump($options));
    }
    $items = $options['items'];

    // check if we have an array variable or a list definition
    if (!is_array($items) && strPos($options, '$') === 0) {
      $items = $GLOBALS[subStr($options, 1)];
      if (!is_array($items)) {
        throw new ConfigurationException("'items' option is no array.");
      }
    }
    // check if we only have values and need to create keys
    else if (array_values($items) === $items) {
      $items = array_combine($items, $items);
    }

    // translate values
    $result = [];
    $message = ObjectFactory::getInstance('message');
    foreach ($items as $curKey => $curValue) {
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
    return true;
  }
}
?>