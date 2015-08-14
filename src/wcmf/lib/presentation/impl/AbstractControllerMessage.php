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

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\format\Format;
use wcmf\lib\presentation\ControllerMessage;
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
   * @see Message::setSender()
   */
  public function setSender($sender) {
    $this->_sender = $sender;
  }

  /**
   * @see Message::getSender()
   */
  public function getSender() {
    return $this->_sender;
  }

  /**
   * @see Message::setContext()
   */
  public function setContext($context) {
    $this->_context = $context;
  }

  /**
   * @see Message::getContext()
   */
  public function getContext() {
    return $this->_context;
  }

  /**
   * @see Message::setAction()
   */
  public function setAction($action) {
    $this->_action = $action;
  }

  /**
   * @see Message::getAction()
   */
  public function getAction() {
    return $this->_action;
  }

  /**
   * @see Message::setFormat()
   */
  public function setFormat(Format $format) {
    $this->_format = $format;
  }

  /**
   * @see Message::getFormat()
   */
  public function getFormat() {
    if ($this->_format == null) {
      $this->_format = self::getFormatFromMimeType($this->getHeader('Content-Type'));
    }
    return $this->_format;
  }

  /**
   * @see Message::setHeader()
   */
  public function setHeader($name, $value) {
    $this->_headers[$name] = $value;
  }

  /**
   * @see Message::setHeaders()
   */
  public function setHeaders(array $headers) {
    $this->_headers = $headers;
  }

  /**
   * @see Message::getHeader()
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
   * @see Message::getHeaders()
   */
  public function getHeaders() {
    return $this->_headers;
  }

  /**
   * @see Message::clearHeader()
   */
  public function clearHeader($name) {
    unset($this->_headers[$name]);
  }

  /**
   * @see Message::clearHeaders()
   */
  public function clearHeaders() {
    $this->_headers = array();
  }

  /**
   * @see Message::hasHeader()
   */
  public function hasHeader($name) {
    return array_key_exists($name, $this->_headers);
  }

  /**
   * @see Message::setValue()
   */
  public function setValue($name, $value) {
    $this->_values[$name] = $value;
  }

  /**
   * @see Message::setValues()
   */
  public function setValues(array $values) {
    $this->_values = $values;
  }

  /**
   * @see Message::getValue()
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
   * @see Message::getBooleanValue()
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
   * @see Message::getValues()
   */
  public function getValues() {
    return $this->_values;
  }

  /**
   * @see Message::clearValue()
   */
  public function clearValue($name) {
    unset($this->_values[$name]);
  }

  /**
   * @see Message::clearValues()
   */
  public function clearValues() {
    $this->_values = array();
  }

  /**
   * @see Message::hasValue()
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->_values);
  }

  /**
   * @see Message::setProperty()
   */
  public function setProperty($name, $value) {
    $this->_properties[$name] = $value;
  }

  /**
   * @see Message::getProperty()
   */
  public function getProperty($name) {
    if (isset($this->_properties[$name])) {
      return $this->_properties[$name];
    }
    return null;
  }

  /**
   * @see Message::addError()
   */
  public function addError(ApplicationError $error) {
    $this->_errors[] = $error;
  }

  /**
   * @see Message::setErrors()
   */
  public function setErrors(array $errors) {
    $this->_errors = $errors;
  }

  /**
   * @see Message::getErrors()
   */
  public function getErrors() {
    return $this->_errors;
  }

 /**
   * @see Message::clearErrors()
   */
  public function clearErrors() {
    $this->_errors = array();
  }

  /**
   * @see Message::hasErrors()
   */
  public function hasErrors() {
    return sizeof($this->_errors) > 0;
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
