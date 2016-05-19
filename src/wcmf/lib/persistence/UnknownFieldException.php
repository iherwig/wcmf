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
namespace wcmf\lib\persistence;

/**
 * UnknownFieldException signals an exception in a message.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UnknownFieldException extends \Exception {

  private $field = '';

  /**
   * Constructor
   * @param $field The name of the field
   * @param $message The error message
   * @param $code The error code
   * @param $previous The previous Exception
   */
  public function __construct($field, $message="", $code=0, \Exception $previous=null) {
    parent::__construct($message, $code, $previous);
    $this->field = $field;
  }

  /**
   * Get the name of the field
   * @return String
   */
  public function getField() {
    return $this->field;
  }
}
?>
