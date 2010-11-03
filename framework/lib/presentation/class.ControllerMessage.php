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

/**
 * Some constants describing the different message formats
 */
define("MSG_FORMAT_HTML", "HTML");
define("MSG_FORMAT_JSON", "JSON");
define("MSG_FORMAT_SOAP", "SOAP");

/**
 * @class ControllerMessage
 * @ingroup Presentation
 * @brief ControllerMessages are sent between Controllers and are used to transfer data
 * between them. ControllerMessages are dispatched by ActionMapper, which decides upon the
 * message's controller, context and action parameter to which Controller it will
 * be send.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ControllerMessage
{
  private $_sender = null;
  private $_context = null;
  private $_action = null;
  private $_format = null;
  private $_data = array();

  /**
   * Constructor
   * @param sender The name of the controller that sent the message
   * @param context The name of the context of the message
   * @param action The name of the action that the message initiates
   * @param data An associative array containing the message variables
   * together with their values.
   */
  public function ControllerMessage($sender, $context, $action, $data)
  {
    if (func_num_args() != 4) {
      throw new ApplicationException("Message constructor called with wrong argument number: ");
    }
    $this->_sender = $sender;
    $this->_context = $context;
    $this->_action = $action;
    $this->_data = $data;
  }

  /**
   * Set the name of the sending Controller
   * @param sender The name of the Controller
   */
  public function setSender($sender)
  {
    $this->_sender = $sender;
  }

  /**
   * Get the name of the sending Controller
   * @return The name of the Controller
   */
  public function getSender()
  {
    return $this->_sender;
  }

  /**
   * Set the name of the context
   * @param context The name of the context
   */
  function setContext($context)
  {
    $this->_context = $context;
  }

  /**
   * Get the name of the context
   * @return The name of the context
   */
  public function getContext()
  {
    return $this->_context;
  }

  /**
   * Set the name of the action
   * @param action The name of the action
   */
  public function setAction($action)
  {
    $this->_action = $action;
  }

  /**
   * Get the name of the action
   * @return The name of the action
   */
  public function getAction()
  {
    return $this->_action;
  }

  /**
   * Set the message format
   * @param format One of the MSG_FORMAT constants
   */
  public function setFormat($format)
  {
    $this->_format = $format;
  }

  /**
   * Get the message format
   * @return format One of the MSG_FORMAT constants
   */
  public function getFormat()
  {
    return $this->_format;
  }

  /**
   * Set a value
   * @param name The name of the variable
   * @param value The value of the variable
   */
  public function setValue($name, $value)
  {
    $this->_data[$name] = $value;
  }

  /**
   * Append a value
   * @param name The name of the variable
   * @param value The value to append to the variable
   */
  public function appendValue($name, $value)
  {
    if (!$this->hasValue($name)) {
      $this->_data[$name] = $value;
    }
    else {
      $this->_data[$name] .= $value;
    }
  }

  /**
   * Check for existance of a value
   * @param name The name of the variable
   * @return True/False wether the value exists or not exist
   */
  public function hasValue($name)
  {
    return array_key_exists($name, $this->_data);
  }

  /**
   * Get a value
   * @param name The name of the variable
   * @param default The default value if the value is not defined [default: null]
   * @return The value or default, if it does not exist
   */
  public function getValue($name, $default=null)
  {
    if (!$this->hasValue($name)) {
      return $default;
    }
    else {
      return $this->_data[$name];
    }
  }

  /**
   * Get a value as boolean
   * @param name The name of the variable
   * @param default The default value if the value is not defined [default: false]
   * @return The value or null if it does not exist
   */
  public function getBooleanValue($name, $default=false)
  {
    if (!$this->hasValue($name)) {
      return $default;
    }
    else {
      return ($this->_data[$name] === true || strtolower($this->_data[$name]) === "true"
        || intval($this->_data[$name]) === 1);
    }
  }

  /**
   * Get all values as an associative array
   * @return A reference to an data array
   */
  public function &getData()
  {
    return $this->_data;
  }

  /**
   * Set all values at once
   * @param data A reference to the data
   */
  public function setData(&$data)
  {
    $this->_data = &$data;
  }

  /**
   * Remove a value
   * @param name The name of the variable
   */
  public function clearValue($name)
  {
    unset($this->_data[$name]);
  }

  /**
   * Remove all values
   */
  public function clearValues()
  {
    $this->_data = array();
  }

  /**
   * Get a string representation of the message
   * @return The string
   */
  public function __toString()
  {
    $str = 'sender='.$this->_sender.', ';
    $str .= 'context='.$this->_context.', ';
    $str .= 'action='.$this->_action.', ';
    $str .= 'format='.$this->_format.', ';
    $str .= 'data='.StringUtil::getDump($this->_data);
    return $str;
  }
}
?>
