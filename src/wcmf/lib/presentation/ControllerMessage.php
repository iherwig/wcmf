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
namespace wcmf\lib\presentation;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\format\Format;
use wcmf\lib\util\StringUtil;

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
   * The message headers
   */
  private $_headers = array();

  /**
   * Key value pairs of data contained in this message.
   */
  private $_values = array();

  /**
   * Key value pairs of user defined properties contained in this message.
   */
  private $_properties = array();

  /**
   * A list of errors associated with this message.
   */
  private $_errors = array();

  /**
   * Constructor
   * @param $sender The name of the controller that sent the message
   * @param $context The name of the context of the message
   * @param $action The name of the action that the message initiates
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
   * @param $sender The name of the Controller
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
   * @param $context The name of the context
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
   * @param $action The name of the action
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
   * @param $format Format instance
   */
  public function setFormat(Format $format) {
    $this->_format = $format;
  }

  /**
   * Get the message format. If no explicit format is set, the
   * format is derived from the Content-Type header value, if existing.
   * If no format can be derived, the first format in the configuration
   * key 'Formats' will be used.
   * @return Format instance
   */
  public function getFormat() {
    if ($this->_format == null) {
      $this->_format = self::getFormatFromMimeType($this->getHeader('Content-Type'));
    }
    return $this->_format;
  }

  /**
   * Set a header value
   * @param $name The header name
   * @param $value The header value
   */
  public function setHeader($name, $value) {
    $this->_headers[$name] = $value;
  }

  /**
   * Get a header value
   * @param $name The header name
   * @param $default The default value if the header is not defined [default: null]
   * @return The header value or default, if it does not exist
   */
  public function getHeader($name, $default=null) {
    if ($this->hasHeader($name)) {
      return $this->_headers[$name];
    }
    else {
      return $default;
    }
  }

  /**
   * Check for existance of a header
   * @param $name The name of the header
   * @return Boolean whether the header exists or not exist
   */
  public function hasHeader($name) {
    return array_key_exists($name, $this->_headers);
  }

  /**
   * Get all key headers
   * @return An associative array
   */
  public function getHeaders() {
    return $this->_headers;
  }

  /**
   * Set all headers at once
   * @param $headers The associative array
   */
  public function setHeaders(array $headers) {
    $this->_headers = $headers;
  }

  /**
   * Remove a header
   * @param $name The name of the header
   */
  public function clearHeader($name) {
    unset($this->_headers[$name]);
  }

  /**
   * Remove all headers
   */
  public function clearHeaders() {
    $this->_headers = array();
  }

  /**
   * Set a value
   * @param $name The name of the variable
   * @param $value The value of the variable
   */
  public function setValue($name, $value) {
    $this->_values[$name] = $value;
  }

  /**
   * Append a value to an existing variable or set it
   * if it does not exist
   * @param $name The name of the variable
   * @param $value The value to append to the variable
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
   * @param $name The name of the variable
   * @return Boolean whether the value exists or not exist
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->_values);
  }

  /**
   * Get a value
   * @param $name The name of the variable
   * @param $default The default value if the value is not defined [optional, default: null]
   * @param $filter PHP filter constant (FILTER_SANITIZE/FILTER_VALIDATE) to be applied on the value [optional]
   * @param $options Filter parameters [optional]
   * @return The (filtered) value or default, if it does not exist
   */
  public function getValue($name, $default=null, $filter=null, $options=null) {
    if ($this->hasValue($name)) {
      $value = $this->_values[$name];
      return ($filter != null) ? filter_var($value, $filter, $options) : $value;
    }
    else {
      return $default;
    }
  }

  /**
   * Get a value as boolean
   * @param $name The name of the variable
   * @param $default The default value if the value is not defined [default: false]
   * @return The value or null if it does not exist
   */
  public function getBooleanValue($name, $default=false) {
    if ($this->hasValue($name)) {
      return StringUtil::getBoolean($this->_values[$name]);
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
   * @param $values The associative array
   */
  public function setValues(array $values) {
    $this->_values = $values;
  }

  /**
   * Remove a value
   * @param $name The name of the variable
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
   * Set a property
   * @param $name The name of the property
   * @param $value The value of the property
   */
  public function setProperty($name, $value) {
    $this->_properties[$name] = $value;
  }

  /**
   * Get a property
   * @param $name The name of the property
   * @return The property value or null
   */
  public function getProperty($name) {
    if (isset($this->_properties[$name])) {
      return $this->_properties[$name];
    }
    return null;
  }

  /**
   * Add an error to the list of errors.
   * @param $error The error.
   */
  public function addError(ApplicationError $error) {
    $this->_errors[] = $error;
  }

  /**
   * Check if errors exist.
   * @return Boolean whether there are errors or not.
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
   * @param $errors The errors array
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
    $str .= 'format='.get_class($this->_format).', ';
    $str .= 'values='.StringUtil::getDump($this->_values);
    $str .= 'errors='.StringUtil::getDump($this->_errors);
    return $str;
  }

  /**
   * Get the format instance for the given mime type.
   * @param $mimeType The mime type
   * @return Format instance
   */
  protected static function getFormatFromMimeType($mimeType) {
    $formats = ObjectFactory::getInstance('formats');
    $firstFormat = null;
    foreach ($formats as $name => $instance) {
      $firstFormat = $firstFormat == null ? $name : $firstFormat;
      if (strpos($mimeType, $instance->getMimeType()) !== false) {
        return $instance;
      }
    }
    if (!isset($formats[$firstFormat])) {
      throw new ConfigurationException("Configuration section 'Formats' does not contain a format definition for: ".$mimeType);
    }
    return $formats[$firstFormat];
  }
}
?>
