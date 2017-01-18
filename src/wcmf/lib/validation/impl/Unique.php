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
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\validation\ValidateType;

/**
 * Unique checks if the value is unique regarding the given entity attribute.
 *
 * Configuration examples:
 * @code
 * // ensure Keyword.name is unique
 * unique:{"type":"Keyword","value":"name"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Unique implements ValidateType {

  /**
   * @see ValidateType::validate
   */
  public function validate($value, $options=null, $context=null) {
    if (strlen($value) == 0) {
      return true;
    }

    if (!isset($options['type'])) {
      throw new ConfigurationException("No 'type' given in unique options: ".json_encode($options));
    }
    if (!isset($options['value'])) {
      throw new ConfigurationException("No 'value' given in unique options: ".json_encode($options));
    }

    $type = $options['type'];
    $attribute = $options['value'];
    $query = new ObjectQuery($type);
    $itemTpl = $query->getObjectTemplate($type);
    // force set to skip validation
    $itemTpl->setValue($attribute, Criteria::asValue("=", $value), true);
    $itemList = $query->execute(false);

    // value already exists
    if (sizeof($itemList) > 0) {
      return false;
    }
    return true;
  }
}
?>
