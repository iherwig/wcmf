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
namespace wcmf\lib\security;

/**
 * AuthenticationManager implementations are used to handle all authentication
 * requests.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface AuthenticationManager {

  /**
   * Authenticate using the given credentials.
   * @param $credentials Associative array with implementation specific keys
   * @return User instance if successful, null else
   */
  public function login($credentials);
}
?>
