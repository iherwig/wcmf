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
require_once(BASE."wcmf/lib/util/class.Storable.php");

/**
 * @class SessionData
 * @ingroup Util
 * @brief This class provides a unified access to session data.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SessionData
{
  private static $_instance = null;
  private static $ERROR_VARNAME = 'SessionData.errors';

  private function __construct() {}

  /**
   * Get the file where the class definitions are stored.
   * @return filename
   */
  private static function getClassDefinitionFile()
  {
      $path = array_pop(split(";", session_save_path()));
      return $path."/sess_cd_".session_id();
  }

  /**
   * Created a SessionData instance with given session id.
   * @param sessionID The session id to use (maybe null).
   * @note If session id is null an automatically generated session id will be used.
   */
  public function init($sessionID)
  {
    if ($sessionID != null)
    {
      // We have a custom session id so we can automatically restore class definitions.

      // Set custom session id
      session_id($sessionID);

      // Restore class definitions before session start
      $filename = SessionData::getClassDefinitionFile();
      if (file_exists($filename) && strpos($filename, BASE) === 0)
      {
        $fp = fopen($filename, "r");
        $classDefs = fread($fp, filesize ($filename));
        fclose($fp);
        foreach (split("\n", $classDefs) as $classDef)
        {
          if ($classDef != '') {
            require_once($classDef);
          }
        }
      }
    }
    session_start();
  }
  /**
   * Returns an instance of the class.
   * @note If called before init an automatically generated session id will be used.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!is_object(self::$_instance)) {
      self::$_instance = new SessionData();
    }
    return self::$_instance;
  }
  /**
   * Get the id of the session.
   * @return The id of the current session.
   */
  public function getID()
  {
    return session_id();
  }
  /**
   * Returns the value of an session variable
   * @param key The key (name) of the session vaiable.
   * @return A reference to the session var or null if it doesn't exist.
   */
  public function get($key)
  {
    $val = null;
    if (array_key_exists($key, $_SESSION))
    {
      $val = $_SESSION[$key];
      if (is_object($val))
      {
        $classMethods = array_map("strtolower", get_class_methods($val));
        if (in_array('loadFromSession', $classMethods)) {
          $val->loadFromSession();
        }
      }
    }
    return $val;
  }
  /**
   * Sets the value of an session variable. If the value is an object it must eiter implement the Storable interface
   * or the classFiles parameter must not be null.
   * @param key The key (name) of the session vaiable.
   * @param val The value of the session variable.
   * @param classFiles An array of definition files needed to be included for rebuilding val from the session [default: null].
   * @return A reference to the session var
   */
  public function set($key, $val, array $classFiles=null)
  {
    // try to store class definition

    if (is_object($val))
    {
      if ($classFiles == null)
      {
        // no class files given -> check for Storable interface
        $classMethods = array_map(strtolower, get_class_methods($val));
        if (!in_array('getclassdefinitionfiles', $classMethods)) {
          throw new IllegalArgumentException("Class ".get_class($val)." does not implement the Storable interface.");
        }
        else
        {
          // Store class definitions of session object
          $classFiles = $val->getClassDefinitionFiles();

          if (in_array('saveToSession', $classMethods)) {
            $val->saveToSession();
          }
        }
      }

      // Store class definitions of session object
      $filename = SessionData::getClassDefinitionFile();
      $classDefsStr = '';
      if (file_exists($filename))
      {
        $fp = fopen($filename, "r");
        $classDefsStr = fread($fp, filesize ($filename));
        fclose($fp);
      }
      $classDefs = preg_split("/\n/", $classDefsStr);
      $fp = fopen($filename, "a");
      foreach ($classFiles as $classFile)
      {
        if (!in_array($classFile, $classDefs)) {
          fwrite($fp, $classFile."\n");
        }
      }
      fclose($fp);
    }
    $_SESSION[$key] = &$val;
  }
  /**
   * Remove a session variable.
   * @param key The key (name) of the session variable.
   */
  public function remove($key)
  {
    unset($_SESSION[$key]);
  }
  /**
   * Tests, if a certain session variable is defined.
   * @param key The key (name) of the session variable.
   * @return A boolean flag. true if the session variable is set, false if not.
   */
  public function exist($key)
  {
    return array_key_exists($key, $_SESSION);
  }
  /**
   * Clear the session data.
   */
  public function clear()
  {
    session_unset();
  }
  /**
   * Add an error to the session data.
   * @param key The identifier of the error
   * @param error The error message
   */
  public function addError($key, $error)
  {
    if (!is_array($_SESSION[self::$ERROR_VARNAME])) {
      $_SESSION[self::$ERROR_VARNAME] = array();
    }
    $_SESSION[self::$ERROR_VARNAME][$key] = $error;
  }
  /**
   * Get an error stored in the session data.
   * @param key The identifier of the error
   * @return The error message
   */
  public function getError($key)
  {
    return $_SESSION[self::$ERROR_VARNAME][$key];
  }
  /**
   * Get all errors stored in the session data.
   * @return The error message
   */
  public function getErrors()
  {
    return $_SESSION[self::$ERROR_VARNAME];
  }
  /**
   * Clear the session error data.
   */
  public function clearErrors()
  {
    unset($_SESSION[self::$ERROR_VARNAME]);
  }
  /**
   * Destroy the session.
   */
  public function destroy()
  {
    // Delete class definition file
    $filename = SessionData::getClassDefinitionFile();
    if (file_exists($filename)) {
      unlink($filename);
    }
    $_SESSION = array();
    session_destroy();
  }
}