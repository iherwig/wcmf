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
  private $sender = null;

  /**
   * The name of the context of the message.
   */
  private $context = null;

  /**
   * The name of the action that should be executed with this message.
   */
  private $action = null;

  /**
   * The format of the message.
   */
  private $format = null;

  /**
   * The formatter used for de-, serialization into the format.
   */
  private $formatter = null;

  /**
   * The message headers
   */
  private $headers = array();

  /**
   * Key value pairs of data contained in this message.
   */
  private $values = array();

  /**
   * Key value pairs of user defined properties contained in this message.
   */
  private $properties = array();

  /**
   * A list of errors associated with this message.
   */
  private $errors = array();

  /**
   * Constructor
   * @param $formatter
   */
  public function __construct(Formatter $formatter) {
    $this->formatter = $formatter;
  }

  /**
   * @see ControllerMessage::setSender()
   */
  public function setSender($sender) {
    $this->sender = $sender;
  }

  /**
   * @see ControllerMessage::getSender()
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * @see ControllerMessage::setContext()
   */
  public function setContext($context) {
    $this->context = $context;
  }

  /**
   * @see ControllerMessage::getContext()
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * @see ControllerMessage::setAction()
   */
  public function setAction($action) {
    $this->action = $action;
  }

  /**
   * @see ControllerMessage::getAction()
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * @see ControllerMessage::setFormat()
   */
  public function setFormat($format) {
    $this->format = $format;
  }

  /**
   * @see ControllerMessage::getFormat()
   */
  public function getFormat() {
    if ($this->format == null) {
      $this->format = $this->formatter->getFormatFromMimeType($this->getHeader('Content-Type'));
    }
    return $this->format;
  }

  /**
   * @see ControllerMessage::setHeader()
   */
  public function setHeader($name, $value) {
    $this->headers[$name] = $value;
  }

  /**
   * @see ControllerMessage::setHeaders()
   */
  public function setHeaders(array $headers) {
    $this->headers = $headers;
  }

  /**
   * @see ControllerMessage::getHeader()
   */
  public function getHeader($name, $default=null) {
    if ($this->hasHeader($name)) {
      return $this->headers[$name];
    }
    else {
      return $default;
    }
  }

  /**
   * @see ControllerMessage::getHeaders()
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * @see ControllerMessage::clearHeader()
   */
  public function clearHeader($name) {
    unset($this->headers[$name]);
  }

  /**
   * @see ControllerMessage::clearHeaders()
   */
  public function clearHeaders() {
    $this->headers = array();
  }

  /**
   * @see ControllerMessage::hasHeader()
   */
  public function hasHeader($name) {
    return array_key_exists($name, $this->headers);
  }

  /**
   * @see ControllerMessage::setValue()
   */
  public function setValue($name, $value) {
    $this->values[$name] = $value;
  }

  /**
   * @see ControllerMessage::setValues()
   */
  public function setValues(array $values) {
    $this->values = $values;
  }

  /**
   * @see ControllerMessage::getValue()
   */
  public function getValue($name, $default=null, $validateDesc=null) {
    if ($this->hasValue($name)) {
      $value = $this->values[$name];
      return $validateDesc === null ? $value :
          (Validator::validate($value, $validateDesc) ? $value : null);
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
      return StringUtil::getBoolean($this->values[$name]);
    }
    else {
      return $default;
    }
  }

  /**
   * @see ControllerMessage::getValues()
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * @see ControllerMessage::clearValue()
   */
  public function clearValue($name) {
    unset($this->values[$name]);
  }

  /**
   * @see ControllerMessage::clearValues()
   */
  public function clearValues() {
    $this->values = array();
  }

  /**
   * @see ControllerMessage::hasValue()
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->values);
  }

  /**
   * @see ControllerMessage::setProperty()
   */
  public function setProperty($name, $value) {
    $this->properties[$name] = $value;
  }

  /**
   * @see ControllerMessage::getProperty()
   */
  public function getProperty($name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name];
    }
    return null;
  }

  /**
   * @see ControllerMessage::addError()
   */
  public function addError(ApplicationError $error) {
    $this->errors[] = $error;
  }

  /**
   * @see ControllerMessage::setErrors()
   */
  public function setErrors(array $errors) {
    $this->errors = $errors;
  }

  /**
   * @see ControllerMessage::getErrors()
   */
  public function getErrors() {
    return $this->errors;
  }

 /**
   * @see ControllerMessage::clearErrors()
   */
  public function clearErrors() {
    $this->errors = array();
  }

  /**
   * @see ControllerMessage::hasErrors()
   */
  public function hasErrors() {
    return sizeof($this->errors) > 0;
  }

  /**
   * Get the Formatter instance
   * @return Formatter
   */
  protected function getFormatter() {
    return $this->formatter;
  }

  /**
   * Get a string representation of the message
   * @return The string
   */
  public function __toString() {
    $str = 'sender='.$this->sender.', ';
    $str .= 'context='.$this->context.', ';
    $str .= 'action='.$this->action.', ';
    $str .= 'format='.$this->format.', ';
    $str .= 'values='.StringUtil::getDump($this->values).', ';
    $str .= 'errors='.StringUtil::getDump($this->errors);
    return $str;
  }
}
?>
