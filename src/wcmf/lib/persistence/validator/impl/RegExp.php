<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\validator\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\validator\ValidateType;

/**
 * RegExp validates against the given regular expression.
 *
 * Configuration example:
 * @code
 * // integer or empty
 * regexp:{"pattern":"^[0-9]*$"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RegExp implements ValidateType {

  /**
   * @see ValidateType::validate
   * $options is an associative array with key 'pattern'
   */
  public function validate($value, Message $message, $options=null) {
    if (!isset($options['pattern'])) {
      throw new ConfigurationException($message->getText("No 'pattern' given in regexp options: %1%"),
              array(json_encode($options)));
    }
    return preg_match("/".$options['pattern']."/m", $value);
  }
}
?>
