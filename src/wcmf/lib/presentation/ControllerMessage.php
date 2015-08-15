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
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\format\Format;

/**
 * Messages are sent between Controllers and are used to transfer data between
 * them. They are are dispatched by ActionMapper, which decides upon the
 * message's controller, context and action parameter to which Controller it will
 * be send.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ControllerMessage {

  /**
   * Set the name of the sending Controller
   * @param $sender The name of the Controller
   */
  public function setSender($sender);

  /**
   * Get the name of the sending Controller
   * @return The name of the Controller
   */
  public function getSender();

  /**
   * Set the name of the context
   * @param $context The name of the context
   */
  public function setContext($context);

  /**
   * Get the name of the context
   * @return The name of the context
   */
  public function getContext();

  /**
   * Set the name of the action
   * @param $action The name of the action
   */
  public function setAction($action);

  /**
   * Get the name of the action
   * @return The name of the action
   */
  public function getAction();

  /**
   * Set the message format
   * @param $format Format instance
   */
  public function setFormat(Format $format);

  /**
   * Set the message format
   * @param $name A key of the configuration section 'Formats'
   */
  public function setFormatByName($name);

  /**
   * Get the message format. If no explicit format is set, the
   * format is derived from the Content-Type header value, if existing.
   * If no format can be derived, the first format in the configuration
   * section 'Formats' will be used.
   * @return Format instance
   */
  public function getFormat();

  /**
   * Set a header value
   * @param $name The header name
   * @param $value The header value
   */
  public function setHeader($name, $value);

  /**
   * Set all headers at once
   * @param $headers The associative array
   */
  public function setHeaders(array $headers);

  /**
   * Get a header value
   * @param $name The header name
   * @param $default The default value if the header is not defined (default: _null_)
   * @return The header value or default, if it does not exist
   */
  public function getHeader($name, $default=null);

  /**
   * Get all key headers
   * @return An associative array
   */
  public function getHeaders() ;

  /**
   * Remove a header
   * @param $name The name of the header
   */
  public function clearHeader($name);

  /**
   * Remove all headers
   */
  public function clearHeaders();

  /**
   * Check for existance of a header
   * @param $name The name of the header
   * @return Boolean whether the header exists or not exist
   */
  public function hasHeader($name);

  /**
   * Set a value
   * @param $name The name of the variable
   * @param $value The value of the variable
   */
  public function setValue($name, $value);

  /**
   * Set all key value pairs at once
   * @param $values The associative array
   */
  public function setValues(array $values);

  /**
   * Get a value
   * @param $name The name of the variable
   * @param $default The default value if the value is not defined (optional, default: _null_)
   * @param $filter PHP filter constant (FILTER_SANITIZE/FILTER_VALIDATE) to be applied to the value (optional, default: _null_)
   * @param $options Filter parameters (optional, default: _null_)
   * @return The (filtered) value or default, if it does not exist
   */
  public function getValue($name, $default=null, $filter=null, $options=null);

  /**
   * Get a value as boolean
   * @param $name The name of the variable
   * @param $default The default value if the value is not defined (default: _false_)
   * @return The value or null if it does not exist
   */
  public function getBooleanValue($name, $default=false);

  /**
   * Get all key value pairs
   * @return An associative array
   */
  public function getValues();

  /**
   * Remove a value
   * @param $name The name of the variable
   */
  public function clearValue($name);

  /**
   * Remove all values
   */
  public function clearValues();

  /**
   * Check for existance of a value
   * @param $name The name of the variable
   * @return Boolean whether the value exists or not exist
   */
  public function hasValue($name);

  /**
   * Set a property
   * @param $name The name of the property
   * @param $value The value of the property
   */
  public function setProperty($name, $value);

  /**
   * Get a property
   * @param $name The name of the property
   * @return The property value or null
   */
  public function getProperty($name);

  /**
   * Add an error to the list of errors.
   * @param $error The error.
   */
  public function addError(ApplicationError $error);

  /**
   * Set all errors at once
   * @param $errors The errors array
   */
  public function setErrors(array $errors);

  /**
   * Get all errors.
   * @return An array of Error instances.
   */
  public function getErrors();

 /**
   * Remove all errors
   */
  public function clearErrors();

  /**
   * Check if errors exist.
   * @return Boolean whether there are errors or not.
   */
  public function hasErrors();
}
?>
