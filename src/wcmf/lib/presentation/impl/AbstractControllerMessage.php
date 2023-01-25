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
namespace wcmf\lib\presentation\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ControllerMessage;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\util\StringUtil;
use wcmf\lib\validation\ValidationException;
use wcmf\lib\validation\Validator;

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
  private string $sender = '';

  /**
   * The name of the context of the message.
   */
  private string $context = '';

  /**
   * The name of the action that should be executed with this message.
   */
  private string $action = '';

  /**
   * The format of the message.
   */
  private ?string $format = null;

  /**
   * The formatter used for de-, serialization into the format.
   */
  private ?Formatter $formatter = null;

  /**
   * The message headers
   */
  private array $headers = [];

  /**
   * Key value pairs of data contained in this message.
   */
  private array $values = [];

  /**
   * Key value pairs of user defined properties contained in this message.
   */
  private array $properties = [];

  /**
   * A list of errors associated with this message.
   */
  private array $errors = [];

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
  public function setSender(string $sender): void {
    $this->sender = $sender;
  }

  /**
   * @see ControllerMessage::getSender()
   */
  public function getSender(): string {
    return $this->sender;
  }

  /**
   * @see ControllerMessage::setContext()
   */
  public function setContext(string $context): void {
    $this->context = $context;
  }

  /**
   * @see ControllerMessage::getContext()
   */
  public function getContext(): string {
    return $this->context;
  }

  /**
   * @see ControllerMessage::setAction()
   */
  public function setAction(string $action): void {
    $this->action = $action;
  }

  /**
   * @see ControllerMessage::getAction()
   */
  public function getAction(): string {
    return $this->action;
  }

  /**
   * @see ControllerMessage::setFormat()
   */
  public function setFormat(string $format): void {
    $this->format = $format;
  }

  /**
   * @see ControllerMessage::getFormat()
   */
  public function getFormat(): string {
    if ($this->format == null) {
      $this->format = $this->formatter->getFormatFromMimeType($this->getHeader('Content-Type'));
    }
    return $this->format;
  }

  /**
   * @see ControllerMessage::setHeader()
   */
  public function setHeader(string $name, $value): void {
    $this->headers[$name] = $value;
  }

  /**
   * @see ControllerMessage::setHeaders()
   */
  public function setHeaders(array $headers): void {
    $this->headers = $headers;
  }

  /**
   * @see ControllerMessage::getHeader()
   */
  public function getHeader(string $name, $default=null) {
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
  public function getHeaders(): array {
    return $this->headers;
  }

  /**
   * @see ControllerMessage::clearHeader()
   */
  public function clearHeader(string $name): void {
    unset($this->headers[$name]);
  }

  /**
   * @see ControllerMessage::clearHeaders()
   */
  public function clearHeaders(): void {
    $this->headers = [];
  }

  /**
   * @see ControllerMessage::hasHeader()
   */
  public function hasHeader(string $name): bool {
    return array_key_exists($name, $this->headers);
  }

  /**
   * @see ControllerMessage::setValue()
   */
  public function setValue(string $name, $value): void {
    $this->values[$name] = $value;
  }

  /**
   * @see ControllerMessage::setValues()
   */
  public function setValues(array $values): void {
    $this->values = $values;
  }

  /**
   * @see ControllerMessage::getValue()
   */
  public function getValue(string $name, $default=null, ?string $validateDesc=null, ?bool $suppressException=false) {
    if ($this->hasValue($name)) {
      $value = $this->values[$name];
      if ($validateDesc === null || Validator::validate($value, $validateDesc, ['request' => $this])) {
        return $value;
      }
      if (!$suppressException) {
        throw new ValidationException($name, $value,
            ObjectFactory::getInstance('message')->getText("The value of '%0%' (%1%) is invalid.", [$name, $value]));
      }
    }
    else {
      return $default;
    }
  }

  /**
   * @see ControllerMessage::getBooleanValue()
   */
  public function getBooleanValue(string $name, ?bool $default=false): ?bool {
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
  public function getValues(): array {
    return $this->values;
  }

  /**
   * @see ControllerMessage::clearValue()
   */
  public function clearValue(string $name): void {
    unset($this->values[$name]);
  }

  /**
   * @see ControllerMessage::clearValues()
   */
  public function clearValues(): void {
    $this->values = [];
  }

  /**
   * @see ControllerMessage::hasValue()
   */
  public function hasValue(string $name): bool {
    return array_key_exists($name, $this->values);
  }

  /**
   * @see ControllerMessage::setProperty()
   */
  public function setProperty(string $name, $value): void {
    $this->properties[$name] = $value;
  }

  /**
   * @see ControllerMessage::getProperty()
   */
  public function getProperty(string $name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name];
    }
    return null;
  }

  /**
   * @see ControllerMessage::addError()
   */
  public function addError(ApplicationError $error): void {
    $this->errors[] = $error;
  }

  /**
   * @see ControllerMessage::setErrors()
   */
  public function setErrors(array $errors): void {
    $this->errors = $errors;
  }

  /**
   * @see ControllerMessage::getErrors()
   */
  public function getErrors(): array {
    return $this->errors;
  }

 /**
   * @see ControllerMessage::clearErrors()
   */
  public function clearErrors(): void {
    $this->errors = [];
  }

  /**
   * @see ControllerMessage::hasErrors()
   */
  public function hasErrors(): bool {
    return sizeof($this->errors) > 0;
  }

  /**
   * Get the Formatter instance
   * @return Formatter
   */
  protected function getFormatter(): Formatter {
    return $this->formatter;
  }

  /**
   * Get a string representation of the message
   * @return string
   */
  public function __toString(): string {
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
