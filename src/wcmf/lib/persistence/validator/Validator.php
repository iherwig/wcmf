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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\config\ConfigurationException;

/**
 * Validator is is the single entry point for validation.
 * It chooses the configured validateType based on the validateTypeDesc parameter
 * from the configuration section 'validators'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Validator {

  /**
   * Validate the given value against the given validateType description.
   * @param $value The value to validate
   * @param $validateTypeDesc A string in the form validateType:options, where
   *     validateType is a key in the configuration section 'validators'
   * @return Boolean
   */
  public static function validate($value, $validateTypeDesc) {

    list($validateTypeName, $validateOptions) = preg_split('/:/', $validateTypeDesc, 2);

    // get the validator that should be used for this value
    $validator = self::getValidateType($validateTypeName);
    return $validator->validate($value, $validateOptions);
  }

  /**
   * Get the ValidateType instance for the given name.
   * @param $validateTypeName The validate type's name
   * @return ValidateType instance
   */
  protected static function getValidateType($validateTypeName) {
    $validatorTypes = ObjectFactory::getInstance('validators');
    if (!isset($validatorTypes[$validateTypeName])) {
      throw new ConfigurationException("Configuration section 'Validators' does not contain a validator named: ".$validateTypeName);
    }
    return $validatorTypes[$validateTypeName];
  }
}
?>
