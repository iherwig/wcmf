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
namespace wcmf\lib\presentation\impl;

use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ControllerMessage;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\util\StringUtil;

/**
 * AbstractControllerMessage is the base class for request/response
 * implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractControllerMessage implements ControllerMessage {

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
   * The format of the message.
   */
  private $_format = null;

  /**
   * The formatter used for de-, serialization into the format.
   */
  private $_formatter = null;

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
   * @param $formatter
   */
  public function __construct(Formatter $formatter) {
    $this->_formatter = $formatter;
  }

  /**
   * @see ControllerMessage::setSender()
   */
  public function setSender($sender) {
    $this->_sender = $sender;
  }

  /**
   * @see ControllerMessage::getSender()
   */
  public function getSender() {
    return $this->_sender;
  }

  /**
   * @see ControllerMessage::setContext()
   */
  public function setContext($context) {
    $this->_context = $context;
  }

  /**
   * @see ControllerMessage::getContext()
   */
  public function getContext() {
    return $this->_context;
  }

  /**
   * @see ControllerMessage::setAction()
   */
  public function setAction($action) {
    $this->_action = $action;
  }

  /**
   * @see ControllerMessage::getAction()
   */
  public function getAction() {
    return $this->_action;
  }

  /**
   * @see ControllerMessage::setFormat()
   */
  public function setFormat($format) {
    $this->_format = $format;
  }

  /**
   * @see ControllerMessage::getFormat()
   */
  public function getFormat() {
    if ($this->_format == null) {
      $this->_format = $this->_formatter->getFormatFromMimeType($this->getHeader('Content-Type'));
    }
    return $this->_format;
  }

  /**
   * @see ControllerMessage::setHeader()
   */
  public function setHeader($name, $value) {
    $this->_headers[$name] = $value;
  }

  /**
   * @see ControllerMessage::setHeaders()
   */
  public function setHeaders(array $headers) {
    $this->_headers = $headers;
  }

  /**
   * @see ControllerMessage::getHeader()
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
   * @see ControllerMessage::getHeaders()
   */
  public function getHeaders() {
    return $this->_headers;
  }

  /**
   * @see ControllerMessage::clearHeader()
   */
  public function clearHeader($name) {
    unset($this->_headers[$name]);
  }

  /**
   * @see ControllerMessage::clearHeaders()
   */
  public function clearHeaders() {
    $this->_headers = array();
  }

  /**
   * @see ControllerMessage::hasHeader()
   */
  public function hasHeader($name) {
    return array_key_exists($name, $this->_headers);
  }

  /**
   * @see ControllerMessage::setValue()
   */
  public function setValue($name, $value) {
    $this->_values[$name] = $value;
  }

  /**
   * @see ControllerMessage::setValues()
   */
  public function setValues(array $values) {
    $this->_values = $values;
  }

  /**
   * @see ControllerMessage::getValue()
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
   * @see ControllerMessage::getBooleanValue()
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
   * @see ControllerMessage::getValues()
   */
  public function getValues() {
    return $this->_values;
  }

  /**
   * @see ControllerMessage::clearValue()
   */
  public function clearValue($name) {
    unset($this->_values[$name]);
  }

  /**
   * @see ControllerMessage::clearValues()
   */
  public function clearValues() {
    $this->_values = array();
  }

  /**
   * @see ControllerMessage::hasValue()
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->_values);
  }

  /**
   * @see ControllerMessage::setProperty()
   */
  public function setProperty($name, $value) {
    $this->_properties[$name] = $value;
  }

  /**
   * @see ControllerMessage::getProperty()
   */
  public function getProperty($name) {
    if (isset($this->_properties[$name])) {
      return $this->_properties[$name];
    }
    return null;
  }

  /**
   * @see ControllerMessage::addError()
   */
  public function addError(ApplicationError $error) {
    $this->_errors[] = $error;
  }

  /**
   * @see ControllerMessage::setErrors()
   */
  public function setErrors(array $errors) {
    $this->_errors = $errors;
  }

  /**
   * @see ControllerMessage::getErrors()
   */
  public function getErrors() {
    return $this->_errors;
  }

 /**
   * @see ControllerMessage::clearErrors()
   */
  public function clearErrors() {
    $this->_errors = array();
  }

  /**
   * @see ControllerMessage::hasErrors()
   */
  public function hasErrors() {
    return sizeof($this->_errors) > 0;
  }

  /**
   * Get the Formatter instance
   * @return Formatter
   */
  protected function getFormatter() {
    return $this->_formatter;
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
