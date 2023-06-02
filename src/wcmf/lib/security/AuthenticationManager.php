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
namespace wcmf\lib\security;

use wcmf\lib\security\principal\User;

/**
 * AuthenticationManager implementations are used to handle all authentication
 * requests.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface AuthenticationManager {

  /**
   * Authenticate using the given credentials.
   * @param array $credentials Array with implementation specific keys
   * @return User instance if successful, null else
   */
  public function login(array $credentials): ?User;
}
?>
