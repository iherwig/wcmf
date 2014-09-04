<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security;

/**
 * AuthenticationManager implementations are used to handle all authentication
 * requests.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface AuthenticationManager {

  /**
   * Create an authenticated user for the given credentials.
   * @param $login The login to use
   * @param $password The password to use
   * @return User instance if successfull, null else
   */
  public function login($login, $password);
}
?>
