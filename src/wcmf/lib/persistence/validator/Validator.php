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
namespace wcmf\lib\persistence\validator;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
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
   *     validateType is a key in the configuration section 'validators' and
   *     options is a JSON encoded string as used in the 'restrictions_match' definition
   * @param $message The Message instance used to provide translations
   * @return Boolean
   */
  public static function validate($value, $validateTypeDesc, Message $message) {

    list($validateTypeName, $validateOptions) = preg_split('/:/', $validateTypeDesc, 2);

    // get the validator that should be used for this value
    $validator = self::getValidateType($validateTypeName);
    $decodedOptions = json_decode($validateOptions, true);
    if ($decodedOptions === null) {
      throw new ConfigurationException($message->getText("No valid JSON format: %1%",
              array($validateOptions)));
    }
    return $validator->validate($value, $message, $decodedOptions);
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
