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
namespace wcmf\lib\validation;

/**
 * ValidateType defines the interface for all validator classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ValidateType {

  /**
   * Validate a given value. The options format is type specific.
   * @param $value The value to validate
   * @param $options Optional implementation specific options passed as an associative array
   * @param $context An associative array describing the validation context (optional)
   * @return Boolean
   */
  public function validate($value, $options=null, $context=null);
}
?>
