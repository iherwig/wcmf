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
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\i18n\Message;
use wcmf\lib\presentation\control\lists\ListStrategy;

/**
 * FixedListStrategy implements a constant list of key/value pairs.
 *
 * Configuration examples:
 * @code
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
  public function getList($options, $language=null) {
    if (!isset($options['items'])) {
      throw new ConfigurationException("No 'items' given in list options: "+$options);
    }
    $items = $options['items'];

    // see if we have an array variable or a list definition
    if (!is_array($items) && strPos($options, '$') === 0) {
      $items = $GLOBALS[subStr($options, 1)];
      if (!is_array($items)) {
        throw new ConfigurationException($options['items']." is no array.");
      }
    }

    // translate values
    $result = array();
    foreach ($items as $key => $value) {
      $result[$key] = Message::get($value, null, $language);
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
