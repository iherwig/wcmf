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
namespace wcmf\lib\security\principal;

use wcmf\lib\security\principal\User;

/**
 * AuthUser provides support for authentication/authorization purposes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface AuthUser extends User {

  /**
   * Log a user into the application.
   * @param $login The login string of the user
   * @param $password The password string of the user
   * @return Boolean whether login succeeded.
   */
  public function login($login, $password);

  /**
   * Checks, if the user is authorized for this action.
   * Returns default policy if action key is not defined.
   * @param $actionKey An action key string
   * @param $defaultPolicy Boolean overriding the default policy
   *   just for this request (optional, @see AuhUser::setDefaultPolicy)
   * @return Boolean whether authorization succeeded
   */
  public function authorize($actionKey, $defaultPolicy=null);

  /**
   * Assign the default policy that is used if no permission is set up
   * for the requested action.
   * @param $val Boolean
   */
  public function setDefaultPolicy($val);

  /**
   * Get the default policy that is used if no permission is set up
   * for the requested action.
   * @return Boolean
   */
  public function getDefaultPolicy();

  /**
   * Get login time of the user.
   * @return A formatted time string.
   */
  public function getLoginTime();
}
?>
