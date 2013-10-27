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
 * $Id: class.ApplicationError.php 1221 2010-07-13 22:24:13Z iherwig $
 */
namespace wcmf\lib\presentation;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\i18n\Message;
use wcmf\lib\util\StringUtil;

/**
 * Predefined error levels
 */
define('ERROR_LEVEL_WARNING', 'warning');
define('ERROR_LEVEL_ERROR',   'error');
define('ERROR_LEVEL_FATAL',   'fatal');

/**
 * @class ApplicationError is used to signal errors that occur
 * while processing a request.
 *
 * This class only allows to use predefined errors by
 * using the ApplicationError::get() method.
 * Errors must be defined in the following way:

 * @code
 * define('GENERAL_WARNING', serialize(array('GENERAL_WARNING',
 *   Message::get('An unspecified warning occured.'), ERROR_LEVEL_WARNING)));
 * @endcode
 *
 * To use the error in the code:
 *
 * @code
 * $error = ApplicationError::get('GENERAL_WARNING', $mySpecificErrorData);
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ApplicationError {

  private $_code = null;
  private $_message = null;
  private $_level = null;
  private $_data = null;

  /**
   * Constructor
   * @param code An error code, describing the type of error
   * @param message An error message which is displayed to the user
   * @param level One of the ERROR_LEVEL constants
   * @param data Some error codes required to transmit further information
   *             to the client, optional [default: null]
   */
  private function __construct($code, $message, $level, $data=null) {
    $this->_code = $code;
    $this->_message = $message;
    $this->_level = $level;
    $this->_data = $data;
  }

  /**
   * Get the error code
   * @return The code
   */
  public function getCode() {
    return $this->_code;
  }

  /**
   * Get the error message
   * @return The message
   */
  public function getMessage() {
    return Message::get($this->_message);
  }

  /**
   * Set the error data
   * @param data Some error codes require to transmit
   *   further information to the client
   */
  public function setData($data) {
    $this->_data = $data;
  }

  /**
   * Get the error data
   * @return The data
   */
  public function getData() {
    return $this->_data;
  }

  /**
   * Get a string representation of the error
   * @return String
   */
  public function __toString() {
    $str = strtoupper($this->_level).": ".$this->_code.": ".$this->_message;
    if ($this->_data) {
      $str .= " Data: ".StringUtil::getDump($this->_data);
    }
    return $str;
  }

  /**
   * Factory method for retrieving a predefind error instance.
   * @param code An error code
   * @param data Some error codes required to transmit further information
   *             to the client, optional [default: null]
   * @return ApplicationError
   */
  public static function get($code, $data=null) {
    if (defined($code)) {
      $def = unserialize(constant($code));
      return new ApplicationError($def[0], $def[1], $def[2], $data);
    }
    else {
      throw new IllegalArgumentException("The error code '".$code."' is not defined");
    }
  }

  /**
   * Factory method for transforming an exception into an ApplicationError instance.
   * @param ex Exception
   * @return ApplicationError
   */
  public static function fromException(\Exception $ex) {
    return new ApplicationError('GENERAL_ERROR', $ex->getMessage(), ERROR_LEVEL_ERROR);
  }
}

/**
 * Predefined errors
 */
define('GENERAL_WARNING', serialize(array('GENERAL_WARNING',
  Message::get('An unspecified warning occured.'), ERROR_LEVEL_WARNING)));
define('GENERAL_ERROR', serialize(array('GENERAL_ERROR',
  Message::get('An unspecified error occured.', ERROR_LEVEL_ERROR))));
define('GENERAL_FATAL', serialize(array('GENERAL_FATAL',
  Message::get('An unspecified fatal error occured.'), ERROR_LEVEL_FATAL)));

define('ACTION_INVALID', serialize(array('ACTION_INVALID',
  Message::get('The requested action is unknown.'), ERROR_LEVEL_ERROR)));
define('SESSION_INVALID', serialize(array('SESSION_INVALID',
  Message::get('The session is invalid.'), ERROR_LEVEL_ERROR)));
define('PARAMETER_MISSING', serialize(array('PARAMETER_MISSING',
  Message::get('One or more parameters are missing.'), ERROR_LEVEL_ERROR)));
define('PARAMETER_INVALID', serialize(array('PARAMETER_INVALID',
  Message::get('One or more parameters are invalid.'), ERROR_LEVEL_ERROR)));
define('OID_INVALID', serialize(array('OID_INVALID',
  Message::get('One or more object ids are invalid.'), ERROR_LEVEL_ERROR)));
define('CLASS_NAME_INVALID', serialize(array('CLASS_NAME_INVALID',
  Message::get('One or more classes are invalid.'), ERROR_LEVEL_ERROR)));

define('AUTHENTICATION_FAILED', serialize(array('AUTHENTICATION_FAILED',
  Message::get('Authentication failed.'), ERROR_LEVEL_ERROR)));

define('LIMIT_NEGATIVE', serialize(array('LIMIT_NEGATIVE',
  Message::get('The passed limit is a negative number.'), ERROR_LEVEL_WARNING)));
define('OFFSET_OUT_OF_BOUNDS', serialize(array('OFFSET_OUT_OF_BOUNDS',
  Message::get('The passed offset is negative or greater than the number of entries matching the parameters.'), ERROR_LEVEL_WARNING)));
define('SORT_FIELD_UNKNOWN', serialize(array('SORT_FIELD_UNKNOWN',
  Message::get('The passed sortFieldName is no valid attribute of the passed class.'), ERROR_LEVEL_ERROR)));
define('SORT_DIRECTION_UNKNOWN', serialize(array('SORT_DIRECTION_UNKNOWN',
  Message::get('The passed sortDirection has an invalid value.'), ERROR_LEVEL_ERROR)));

define('DEPTH_INVALID', serialize(array('DEPTH_INVALID',
  Message::get('The passed depth is a negative number other than -1.'), ERROR_LEVEL_ERROR)));

define('ATTRIBUTE_NAME_INVALID', serialize(array('ATTRIBUTE_NAME_INVALID',
  Message::get('The attribute name passed cannot be found in the selected class.'), ERROR_LEVEL_ERROR)));
define('ATTRIBUTE_VALUE_INVALID', serialize(array('ATTRIBUTE_VALUE_INVALID',
  Message::get('The attribute value passed is invalid for the attribute.'), ERROR_LEVEL_ERROR)));
define('CONCURRENT_UPDATE', serialize(array('CONCURRENT_UPDATE',
  Message::get('The server detected a concurrent update.'), ERROR_LEVEL_ERROR)));

define('ROLE_INVALID', serialize(array('ROLE_INVALID',
  Message::get('The role passed cannot be found in the selected source class.'), ERROR_LEVEL_ERROR)));
define('ASSOCIATION_INVALID', serialize(array('ASSOCIATION_INVALID',
  Message::get('There is no association between the source and the target class.'), ERROR_LEVEL_ERROR)));
define('ASSOCIATION_NOT_FOUND', serialize(array('ASSOCIATION_NOT_FOUND',
  Message::get('No current association matching the input parameters can be found.'), ERROR_LEVEL_WARNING)));

define('SEARCH_NOT_SUPPORTED', serialize(array('SEARCH_NOT_SUPPORTED',
  Message::get('There selected class does not support searching.'), ERROR_LEVEL_ERROR)));

define('ORDER_UNDEFINED', serialize(array('ORDER_UNDEFINED',
  Message::get('There is no order defined for the root object.'), ERROR_LEVEL_WARNING)));

define('REFERENCE_INVALID', serialize(array('REFERENCE_INVALID',
  Message::get('There reference object cannot be found in the container object.'), ERROR_LEVEL_ERROR)));
define('ORDER_NOT_SUPPORTED', serialize(array('ORDER_NOT_SUPPORTED',
  Message::get('The container class does not support ordered references.'), ERROR_LEVEL_ERROR)));

define('CLASSES_DO_NOT_MATCH', serialize(array('CLASSES_DO_NOT_MATCH',
  Message::get('The classes of insertOid and referenceOid do not match.'), ERROR_LEVEL_ERROR)));

define('HISTORY_NOT_SUPPORTED', serialize(array('HISTORY_NOT_SUPPORTED',
  Message::get('There selected class does not support history.'), ERROR_LEVEL_ERROR)));

define('PERMISSION_DENIED', serialize(array('PERMISSION_DENIED',
  Message::get('The user does not have the permission to perform this action.'), ERROR_LEVEL_ERROR)));

define('OBJECT_IS_LOCKED', serialize(array('OBJECT_IS_LOCKED',
  Message::get('The object is currently locked by another user.'), ERROR_LEVEL_ERROR)));
?>
