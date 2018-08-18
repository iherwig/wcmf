<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal;

/**
 * The PasswordService class provides services for password handling
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PasswordService {

  /**
   * Check if the given password is hashed
   * @param $password
   * @return boolean
   */
  public static function isHashed($password) {
    $info = password_get_info($password);
    return $info['algo'] == PASSWORD_BCRYPT;
  }

  /**
   * Hash the given cleartext password
   * @param $password
   * @return String
   */
  public static function hash($password) {
    return password_hash($password, PASSWORD_BCRYPT);
  }

  /**
   * Check if the given hash represents the given password
   * @param $password
   * @param $passwordHash
   * @return String
   */
  public static function verify($password, $passwordHash) {
    return password_verify($password, $passwordHash);
  }
}
