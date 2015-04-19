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
namespace wcmf\lib\persistence\validator;

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
   * @return Boolean
   */
  function validate($value, $options=null);
}
?>
