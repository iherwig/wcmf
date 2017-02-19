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
namespace wcmf\lib\util;

use wcmf\lib\core\Session;

/**
 * Obfuscator allows to obfuscate strings. By passing an obfuscated string
 * to the method Obfuscator::unveil() the original string is returned.
 * This is especially useful, if you want to place a secret string inside a client view
 * as a parameter and want to get the original string back as the request is processed.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Obfuscator {

  // session name constants
  const VALUES_VARNAME = 'Obfuscator.values';

  private $session = null;

  /**
   * Constructor
   * @param $session
   */
  public function __construct(Session $session) {
    $this->session = $session;
  }

  /**
   * Get an obfuscated string
   * @param $str The original sring
   * @return The obfuscated string
   */
  public function obfuscate($str) {
    if (strlen($str) == 0) {
      return '';
    }
    $this->ensureStorage();

    // create and store the value
    $obfuscated = md5($str);
    $values = $this->session->get(self::VALUES_VARNAME);
    $values[$obfuscated] = $str;
    $this->session->set(self::VALUES_VARNAME, $values);

    return $obfuscated;
  }

  /**
   * Get an unveiled string
   * @param $str The obfuscated sring
   * @return The original string or an empty string if it does not exist
   */
  public function unveil($str) {
    $this->ensureStorage();

    $values = $this->session->get(self::VALUES_VARNAME);
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
    if (!$this->session->exist(self::VALUES_VARNAME)) {
      $values = [];
      $this->session->set(self::VALUES_VARNAME, $values);
    }
  }
}
?>