<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\presentation;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\presentation\ApplicationError;

/**
 * ControllerMessages are sent between Controllers and are used to transfer data
 * between them. ControllerMessages are dispatched by ActionMapper, which decides upon the
 * message's controller, context and action parameter to which Controller it will
 * be send.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ControllerMessage {

  /**
   * The name of the controller from which the message origins.
   */
  private $_sender = null;

  /**
   * The name of the context of the message.
   */
  private $_context = null;

  /**
   * The name of the action that should be executed with this message.
   */
  private $_action = null;

  /**
   * The format of the message (used for de-, serialization).
   */
  private $_format = null;

  /**
   * Key value pairs of data contained in this message.
   */
  private $_values = array();

  /**
   * A list of errors associated with this message.
   */
  private $_errors = array();

  /**
   * Constructor
   * @param sender The name of the controller that sent the message
   * @param context The name of the context of the message
   * @param action The name of the action that the message initiates
   * together with their values.
   */
  public function __construct($sender, $context, $action) {
    if (func_num_args() != 3) {
      throw new IllegalArgumentException("Message constructor called with wrong argument number.");
    }
    $this->_sender = $sender;
    $this->_context = $context;
    $this->_action = $action;
  }

  /**
   * Set the name of the sending Controller
   * @param sender The name of the Controller
   */
  public function setSender($sender) {
    $this->_sender = $sender;
  }

  /**
   * Get the name of the sending Controller
   * @return The name of the Controller
   */
  public function getSender() {
    return $this->_sender;
  }

  /**
   * Set the name of the context
   * @param context The name of the context
   */
  function setContext($context) {
    $this->_context = $context;
  }

  /**
   * Get the name of the context
   * @return The name of the context
   */
  public function getContext() {
    return $this->_context;
  }

  /**
   * Set the name of the action
   * @param action The name of the action
   */
  public function setAction($action) {
    $this->_action = $action;
  }

  /**
   * Get the name of the action
   * @return The name of the action
   */
  public function getAction() {
    return $this->_action;
  }

  /**
   * Set the message format
   * @param format One of the MSG_FORMAT constants
   */
  public function setFormat($format) {
    $this->_format = $format;
  }

  /**
   * Get the message format
   * @return format One of the MSG_FORMAT constants
   */
  public function getFormat() {
    return $this->_format;
  }

  /**
   * Set a value
   * @param name The name of the variable
   * @param value The value of the variable
   */
  public function setValue($name, $value) {
    $this->_values[$name] = $value;
  }

  /**
   * Append a value to an existing variable or set it
   * if it does not exist
   * @param name The name of the variable
   * @param value The value to append to the variable
   */
  public function appendValue($name, $value) {
    if (!$this->hasValue($name)) {
      $this->_values[$name] = $value;
    }
    else {
      $this->_values[$name] .= $value;
    }
  }

  /**
   * Check for existance of a value
   * @param name The name of the variable
   * @return True/False wether the value exists or not exist
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->_values);
  }

  /**
   * Get a value
   * @param name The name of the variable
   * @param default The default value if the value is not defined [default: null]
   * @return The value or default, if it does not exist
   */
  public function getValue($name, $default=null) {
    if ($this->hasValue($name)) {
      return $this->_values[$name];
    }
    else {
      return $default;
    }
  }

  /**
   * Get a value as boolean
   * @param name The name of the variable
   * @param default The default value if the value is not defined [default: false]
   * @return The value or null if it does not exist
   */
  public function getBooleanValue($name, $default=false) {
    if ($this->hasValue($name)) {
       return ($this->_values[$name] === true || strtolower($this->_values[$name]) === "true"
        || intval($this->_values[$name]) === 1);
    }
    else {
      return $default;
    }
  }

  /**
   * Get all key value pairs
   * @return An associative array
   */
  public function getValues() {
    return $this->_values;
  }

  /**
   * Set all key value pairs at once
   * @param values The associative array
   */
  public function setValues(array $values) {
    $this->_values = $values;
  }

  /**
   * Remove a value
   * @param name The name of the variable
   */
  public function clearValue($name) {
    unset($this->_values[$name]);
  }

  /**
   * Remove all values
   */
  public function clearValues() {
    $this->_values = array();
  }

  /**
   * Add an error to the list of errors.
   * @param error The error.
   */
  public function addError(ApplicationError $error) {
    $this->_errors[] = $error;
  }

  /**
   * Check if errors exist.
   * @return True/False wether there are errors or not.
   */
  public function hasErrors() {
    return sizeof($this->_errors) > 0;
  }

  /**
   * Get all errors.
   * @return An array of Error instances.
   */
  public function getErrors() {
    return $this->_errors;
  }

  /**
   * Set all errors at once
   * @param errora The errors array
   */
  public function setErrors(array $errors) {
    $this->_errors = $errors;
  }

 /**
   * Remove all errors
   */
  public function clearErrors() {
    $this->_errors = array();
  }

  /**
   * Get a string representation of the message
   * @return The string
   */
  public function __toString() {
    $str = 'sender='.$this->_sender.', ';
    $str .= 'context='.$this->_context.', ';
    $str .= 'action='.$this->_action.', ';
    $str .= 'format='.$this->_format.', ';
    $str .= 'values='.StringUtil::getDump($this->_values);
    $str .= 'errors='.StringUtil::getDump($this->_errors);
    return $str;
  }
}
?>
