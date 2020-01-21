<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\validation;

/**
 * ValidationException signals an exception in validation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValidationException extends \Exception {

  private $field = null;
  private $value = null;

  /**
   * Constructor
   * @param $field The name of the field
   * @param $value The value of the field
   * @param $message The error message
   * @param $code The error code
   * @param $previous The previous Exception
   */
  public function __construct($field, $value, $message="", $code=0, \Exception $previous=null) {
    parent::__construct($message, $code, $previous);
    $this->field = $field;
    $this->value = $value;
  }

  /**
   * Get the name of the field
   * @return String
   */
  public function getField() {
    return $this->field;
  }

  /**
   * Get the value of the field
   * @return String
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Get a string representation of the exception
   * @return String
   */
  public function __toString() {
    $msg = '';
    if ($this->field != null) {
      $msg .= 'Invalid value ('.$this->value.') for '.$this->field.': ';
    }
    return $msg.$this->getMessage();
  }
}
?>
