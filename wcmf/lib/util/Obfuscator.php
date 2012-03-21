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
namespace wcmf\lib\util;

use wcmf\lib\core\Session;
use wcmf\lib\util\Obfuscator;

/**
 * Obfuscator allows to obfuscate strings. By passing an objuscated string
 * to the method Obfuscator::unveil() the orginal string is returned.
 * This is especially useful, if you want to place a secret string inside a client view
 * as a parameter and want to get the original string back as the request is processed.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Obfuscator {

  private static $_instance = null;

  // session name constants
  private static $VALUES_VARNAME = 'Obfuscator.values';

  private function __construct() {}

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance() {
    if (!isset(self::$_instance)) {
      self::$_instance = new Obfuscator();
    }
    return self::$_instance;
  }

  /**
   * Get an obfuscated string
   * @param str The original sring
   * @return The obfuscated string
   */
  public static function obfuscate($str) {
    if (strlen($str) == 0) {
      return '';
    }
    $obfuscator = self::getInstance();
    $session = Session::getInstance();
    $obfuscator->ensureStorage();

    // create and store the value
    $obfuscated = md5($str);
    $values = $session->get(self::$VALUES_VARNAME);
    $values[$obfuscated] = $str;
    $session->set(self::$VALUES_VARNAME, $values);

    return $obfuscated;
  }

  /**
   * Get an unveiled string
   * @param str The obfuscated sring
   * @return The original string or an empty string if it does not exist
   */
  public static function unveil($str) {
    $obfuscator = self::getInstance();
    $session = Session::getInstance();
    $obfuscator->ensureStorage();

    $values = $session->get(self::$VALUES_VARNAME);
    if (isset($values[$str])) {
      return $values[$str];
    }
    else {
      return $str;
    }
  }

  /**
   * Ensure that the session storage for the values is initialized
   */
  private function ensureStorage() {
    $session = Session::getInstance();
    if (!$session->exist(self::$VALUES_VARNAME)) {
      $values = array();
      $session->set(self::$VALUES_VARNAME, $values);
    }
  }
}
?>