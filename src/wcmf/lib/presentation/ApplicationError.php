<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\util\StringUtil;

/**
 * ApplicationError is used to signal errors that occur
 * while processing a request.
 *
 * This class allows to use predefined errors by using the ApplicationError::get()
 * method. Errors are defined in the following way:

 * @code
 * $message = ObjectFactory::getInstance('message');
 * define('GENERAL_ERROR', serialize(array('GENERAL_ERROR', ApplicationError::LEVEL_ERROR, 400,
 *   $message->getText('An unspecified error occured.')
 * )));
 * @endcode
 *
 * To use the error in the code:
 *
 * @code
 * $error = ApplicationError::get('GENERAL_ERROR', $mySpecificErrorData);
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ApplicationError {

  // Error levels
  const LEVEL_WARNING = 'warning';
  const LEVEL_ERROR = 'error';
  const LEVEL_FATAL = 'fatal';

  const DEFAULT_ERROR_STATUS = 500;

  private $code = null;
  private $level = null;
  private $statusCode = self::DEFAULT_ERROR_STATUS;
  private $message = null;
  private $data = null;

  private static $predefined = false;

  /**
   * Constructor
   * @param $code An error code, describing the type of error
   * @param $level One of the LEVEL_ constants
   * "param $statusCode The HTTP status code that should be sent with this error
   * @param $message An error message which is displayed to the user
   * @param $data Some error codes required to transmit further information
   *             to the client (optional, default: _null_)
   */
  private function __construct($code, $level, $statusCode, $message, $data=null) {
    $this->code = $code;
    $this->level = $level;
    $this->statusCode = $statusCode;
    $this->message = $message;
    $this->data = $data;
  }

  /**
   * Get the error code
   * @return String
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * Get the error level
   * @return String
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * Get the associated HTTP status code
   * @return Integer
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * Get the error message
   * @return String
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Set the error data
   * @param $data Some error codes require to transmit
   *   further information to the client
   */
  public function setData($data) {
    $this->data = $data;
  }

  /**
   * Get the error data
   * @return Mixed
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Get a string representation of the error
   * @return String
   */
  public function __toString() {
    $str = strtoupper($this->level).": ".$this->code." (".$this->statusCode.
            "): ".$this->message;
    if ($this->data) {
      $str .= " Data: ".StringUtil::getDump($this->data);
    }
    return $str;
  }

  /**
   * Factory method for retrieving a predefined error instance.
   * @param $code An error code
   * @param $data Some error codes required to transmit further information
   *             to the client (optional, default: _null_)
   * @return ApplicationError
   */
  public static function get($code, $data=null) {
    self::predefine();
    if (defined($code)) {
      $def = unserialize(constant($code));
      $error = new ApplicationError($def[0], $def[1], $def[2], $def[3], $data);
      return $error;
    }
    else {
      throw new IllegalArgumentException("The error code '".$code."' is not defined");
    }
  }

  /**
   * Factory method for creating a general error instance.
   * @param $message Error message
   * @param $statusCode HTTP status code (optional, default: _DEFAULT_ERROR_STATUS_)
   * @return ApplicationError
   */
  public static function getGeneral($message, $statusCode=self::DEFAULT_ERROR_STATUS) {
    $error = new ApplicationError('GENERAL_ERROR', ApplicationError::LEVEL_ERROR, $statusCode, $message);
    return $error;
  }

  /**
   * Factory method for transforming an exception into an ApplicationError instance.
   * @param $ex Exception
   * @return ApplicationError
   */
  public static function fromException(\Exception $ex) {
    if ($ex instanceof ApplicationException) {
      return $ex->getError();
    }
    return self::getGeneral($ex->getMessage());
  }

  /**
   * Predefined errors
   */
  private static function predefine() {
    if (!self::$predefined) {
      $message = ObjectFactory::getInstance('message');
      define('GENERAL_WARNING', serialize(array('GENERAL_WARNING', ApplicationError::LEVEL_WARNING, 400,
        $message->getText('An unspecified warning occured.')
      )));
      define('GENERAL_ERROR', serialize(array('GENERAL_ERROR', ApplicationError::LEVEL_ERROR, 500,
        $message->getText('An unspecified error occured.')
      )));
      define('GENERAL_FATAL', serialize(array('GENERAL_FATAL', ApplicationError::LEVEL_FATAL, 500,
        $message->getText('An unspecified fatal error occured.')
      )));

      define('ACTION_INVALID', serialize(array('ACTION_INVALID', ApplicationError::LEVEL_ERROR, 404,
        $message->getText('The requested action is unknown.')
      )));
      define('SESSION_INVALID', serialize(array('SESSION_INVALID', ApplicationError::LEVEL_ERROR, 401,
        $message->getText('The session is invalid.')
      )));
      define('PARAMETER_MISSING', serialize(array('PARAMETER_MISSING', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('One or more parameters are missing.')
      )));
      define('PARAMETER_INVALID', serialize(array('PARAMETER_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('One or more parameters are invalid.')
      )));
      define('OID_INVALID', serialize(array('OID_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('One or more object ids are invalid.')
      )));
      define('CLASS_NAME_INVALID', serialize(array('CLASS_NAME_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('One or more classes are invalid.')
      )));

      define('AUTHENTICATION_FAILED', serialize(array('AUTHENTICATION_FAILED', ApplicationError::LEVEL_ERROR, 401,
        $message->getText('Authentication failed.')
      )));
      define('PERMISSION_DENIED', serialize(array('PERMISSION_DENIED', ApplicationError::LEVEL_ERROR, 403,
        $message->getText('The user does not have the permission to perform this action.')
      )));

      define('LIMIT_NEGATIVE', serialize(array('LIMIT_NEGATIVE', ApplicationError::LEVEL_WARNING, 400,
        $message->getText('The passed limit is a negative number.')
      )));
      define('OFFSET_OUT_OF_BOUNDS', serialize(array('OFFSET_OUT_OF_BOUNDS', ApplicationError::LEVEL_WARNING, 400,
        $message->getText('The passed offset is negative or greater than the number of entries matching the parameters.')
      )));
      define('SORT_FIELD_UNKNOWN', serialize(array('SORT_FIELD_UNKNOWN', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The passed sortFieldName is no valid attribute of the passed class.')
      )));
      define('SORT_DIRECTION_UNKNOWN', serialize(array('SORT_DIRECTION_UNKNOWN', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The passed sortDirection has an invalid value.')
      )));

      define('DEPTH_INVALID', serialize(array('DEPTH_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The passed depth is a negative number other than -1.')
      )));

      define('ATTRIBUTE_NAME_INVALID', serialize(array('ATTRIBUTE_NAME_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The attribute name passed cannot be found in the selected class.')
      )));
      define('ATTRIBUTE_VALUE_INVALID', serialize(array('ATTRIBUTE_VALUE_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The attribute value passed is invalid for the attribute.')
      )));
      define('CONCURRENT_UPDATE', serialize(array('CONCURRENT_UPDATE', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The server detected a concurrent update.')
      )));

      define('ROLE_INVALID', serialize(array('ROLE_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The role passed cannot be found in the selected source class.')
      )));
      define('ASSOCIATION_INVALID', serialize(array('ASSOCIATION_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('There is no association between the source and the target class.')
      )));
      define('ASSOCIATION_NOT_FOUND', serialize(array('ASSOCIATION_NOT_FOUND', ApplicationError::LEVEL_WARNING, 400,
        $message->getText('No current association matching the input parameters can be found.')
      )));

      define('SEARCH_NOT_SUPPORTED', serialize(array('SEARCH_NOT_SUPPORTED', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('There selected class does not support searching.')
      )));

      define('ORDER_UNDEFINED', serialize(array('ORDER_UNDEFINED', ApplicationError::LEVEL_WARNING, 400,
        $message->getText('There is no order defined for the root object.')
      )));

      define('REFERENCE_INVALID', serialize(array('REFERENCE_INVALID', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('There reference object cannot be found in the container object.')
      )));
      define('ORDER_NOT_SUPPORTED', serialize(array('ORDER_NOT_SUPPORTED', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The container class does not support ordered references.')
      )));

      define('CLASSES_DO_NOT_MATCH', serialize(array('CLASSES_DO_NOT_MATCH', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The classes of insertOid and referenceOid do not match.')
      )));

      define('HISTORY_NOT_SUPPORTED', serialize(array('HISTORY_NOT_SUPPORTED', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('There selected class does not support history.')
      )));

      define('OBJECT_IS_LOCKED', serialize(array('OBJECT_IS_LOCKED', ApplicationError::LEVEL_ERROR, 400,
        $message->getText('The object is currently locked by another user.')
      )));

      self::$predefined = true;
    }
  }
}
?>
