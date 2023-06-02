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
namespace wcmf\lib\security\principal;

/**
 * The PasswordService class provides services for password handling
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PasswordService {

  /**
   * Check if the given password is hashed
   * @param string $password
   * @return bool
   */
  public static function isHashed(string $password): bool {
    $info = password_get_info($password);
    return $info['algo'] == PASSWORD_BCRYPT;
  }

  /**
   * Hash the given cleartext password
   * @param string $password
   * @return string
   */
  public static function hash(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
  }

  /**
   * Check if the given hash represents the given password
   * @param string $password
   * @param string $passwordHash
   * @return bool
   */
  public static function verify(string $password, string $passwordHash): bool {
    return password_verify($password, $passwordHash);
  }
}
