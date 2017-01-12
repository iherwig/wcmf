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
namespace wcmf\lib\validation\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\i18n\Message;
use wcmf\lib\validation\ValidateType;

/**
 * Filter validates against the given php filter.
 *
 * Configuration examples:
 * @code
 * // FILTER_VALIDATE_INT with min_range option and FILTER_FLAG_ALLOW_HEX flag
 * filter:{"type":"int","options":{"options":{"min_range":0},"flags":2}}
 *
 * // FILTER_VALIDATE_BOOLEAN simple and with FILTER_NULL_ON_FAILURE flag
 * filter:{"type":"boolean"}
 * filter:{"type":"boolean","options":{"flags":134217728}}
 *
 * // FILTER_VALIDATE_REGEXP with regexp option
 * filter:{"type":"validate_regexp","options":{"options":{"regexp":"/^[0-9]*$/"}}}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Filter implements ValidateType {

  /**
   * @see ValidateType::validate
   * $options is an associative array with keys 'type' and 'options' (optional)
   */
  public function validate($value, Message $message, $options=null) {
    if (!isset($options['type'])) {
      throw new ConfigurationException($message->getText("No 'type' given in filter options: %1%",
              array(json_encode($options))));
    }
    $filterName = $options['type'];
    $filterOptions = isset($options['options']) ? $options['options'] : null;
    return filter_var($value, filter_id($filterName), $filterOptions) !== false;
  }
}
?>
