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
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\ApplicationError;

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
   * @param string $sender The name of the Controller
   */
  public function setSender(string $sender): void;

  /**
   * Get the name of the sending Controller
   * @return string name of the Controller
   */
  public function getSender(): string;

  /**
   * Set the name of the context
   * @param string $context The name of the context
   */
  public function setContext(string $context): void;

  /**
   * Get the name of the context
   * @return string name of the context
   */
  public function getContext(): string;

  /**
   * Set the name of the action
   * @param string $action The name of the action
   */
  public function setAction(string $action): void;

  /**
   * Get the name of the action
   * @return string name of the action
   */
  public function getAction(): string;

  /**
   * Set the message format
   * @param string $format A key of the configuration section 'Formats'
   */
  public function setFormat(string $format): void;

  /**
   * Get the message format. If no explicit format is set, the
   * format is derived from the Content-Type header value, if existing.
   * If no format can be derived, the first format in the configuration
   * section 'Formats' will be used.
   * @return string
   */
  public function getFormat(): string;

  /**
   * Set a header value
   * @param string $name The header name
   * @param mixed $value The header value
   */
  public function setHeader(string $name, $value): void;

  /**
   * Set all headers at once
   * @param array<string, mixed> $headers The header names and values
   */
  public function setHeaders(array $headers): void;

  /**
   * Get a header value
   * @param string $name The header name
   * @param mixed $default The default value if the header is not defined (default: _null_)
   * @return mixed header value or default, if it does not exist
   */
  public function getHeader(string $name, $default=null);

  /**
   * Get all key headers
   * @return array<string, mixed>
   */
  public function getHeaders(): array;

  /**
   * Remove a header
   * @param string $name The name of the header
   */
  public function clearHeader(string $name): void;

  /**
   * Remove all headers
   */
  public function clearHeaders(): void;

  /**
   * Check for existence of a header
   * @param string $name The name of the header
   * @return bool whether the header exists or not exist
   */
  public function hasHeader(string $name): bool;

  /**
   * Set a value
   * @param string $name The name of the variable
   * @param mixed $value The value of the variable
   */
  public function setValue(string $name, $value): void;

  /**
   * Set all key value pairs at once
   * @param array<string, mixed> $values The associative array
   */
  public function setValues(array $values): void;

  /**
   * Get a value
   * @param string $name The name of the variable
   * @param mixed $default The default value if the value is not defined or invalid while exceptions are suppressed (optional, default: _null_)
   * @param string $validateDesc An validation description to be used with Validator::validate() (optional, default: _null_)
   * @param bool $suppressException Boolean whether to suppress a validation exception or not (optional, default: _false_)
   * @return mixed (filtered) value or default, if it does not exist
   */
  public function getValue(string $name, $default=null, string $validateDesc=null, bool $suppressException=false);

  /**
   * Get a value as boolean
   * @param string $name The name of the variable
   * @param bool $default The default value if the value is not defined (default: _false_)
   * @return bool value or null if it does not exist
   */
  public function getBooleanValue(string $name, bool $default=false): ?bool;

  /**
   * Get all key value pairs
   * @return array<string, mixed>
   */
  public function getValues(): array;

  /**
   * Remove a value
   * @param string $name The name of the variable
   */
  public function clearValue(string $name): void;

  /**
   * Remove all values
   */
  public function clearValues(): void;

  /**
   * Check for existence of a value
   * @param string $name The name of the variable
   * @return bool whether the value exists or not exist
   */
  public function hasValue(string $name): bool;

  /**
   * Set a property
   * @param string $name The name of the property
   * @param mixed $value The value of the property
   */
  public function setProperty(string $name, $value): void;

  /**
   * Get a property
   * @param string $name The name of the property
   * @return mixed property value or null
   */
  public function getProperty(string $name);

  /**
   * Add an error to the list of errors.
   * @param ApplicationError $error
   */
  public function addError(ApplicationError $error): void;

  /**
   * Set all errors at once
   * @param array<ApplicationError> $errors The errors array
   */
  public function setErrors(array $errors): void;

  /**
   * Get all errors.
   * @return array<ApplicationError> array of Error instances.
   */
  public function getErrors(): array;

 /**
   * Remove all errors
   */
  public function clearErrors(): void;

  /**
   * Check if errors exist.
   * @return bool whether there are errors or not.
   */
  public function hasErrors(): bool;

  /**
   * Get a string representation of the message
   * @return string
   */
  public function __toString(): string;
}
?>
