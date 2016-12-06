<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\validation;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;

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
   * @param $validateDesc A string in the form validateTypeA,validateTypeB:optionsB, where
   *     validateType is a key in the configuration section 'validators' and
   *     options is a JSON encoded object as used in the 'restrictions_match' definition
   * @param $message The Message instance used to provide translations
   * @return Boolean
   */
  public static function validate($value, $validateDesc, Message $message) {

    // get validator list
    $validators = array();

    // split validateTypeDesc by commas and colons (separates validateType from options)
    $validateDescParts = array();
    preg_match_all('/\{(?:[^{}]|(?R))+\}|[^{}:,\s]+/', $validateDesc, $validateDescParts);
    // combine validateType and options again
    foreach ($validateDescParts[0] as $validateDescPart) {
      if (preg_match('/^\{.*?\}$/', $validateDescPart)) {
        // options of preciding validator
        $numValidators = sizeof($validators);
        if ($numValidators > 0) {
          $validators[$numValidators-1] .= ':'.$validateDescPart;
        }
      }
      else {
        $validators[] = $validateDescPart;
      }
    }

    // validate against each validator
    foreach ($validators as $validator) {
      list($validateTypeName, $validateOptions) = preg_split('/:/', $validator, 2);

      // get the validator that should be used for this value
      $validatorInstance = self::getValidateType($validateTypeName);
      $decodedOptions = json_decode($validateOptions, true);
      if ($decodedOptions === null) {
        throw new ConfigurationException($message->getText("No valid JSON format: %1%",
                array($validateOptions)));
      }
      if (!$validatorInstance->validate($value, $message, $decodedOptions)) {
        return false;
      }
    }

    // all validators passed
    return true;
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
