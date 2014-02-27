<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
   * @param login The login string of the user
   * @param password The password string of the user
   * @return Boolean whether login succeeded.
   */
  public function login($login, $password);

  /**
   * Checks, if the user is authorized for this action.
   * Returns default policy if action key is not defined.
   * @param actionKey An action key string
   * @return Boolean whether authorization succeeded
   */
  public function authorize($actionKey);

  /**
   * Assign the default policy that is used if no permission is set up
   * for the requested action.
   * @param val A boolean value.
   */
  public function setDefaultPolicy($val);

  /**
   * Get login time of the user.
   * @return A formatted time string.
   */
  public function getLoginTime();
}
?>
