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
require_once(WCMF_BASE."wcmf/lib/security/AuthUser.php");

/**
 * @class AnonymousUser
 * @ingroup Security
 * @brief Anonymous User
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AnonymousUser extends AuthUser
{
  /**
   * @see AuthUser::login()
   */
  public function login($login, $password, $isPasswordEncrypted=false)
  {
    // do nothing
  }
  /**
   * @see AuthUser::login()
   */
  public function getLogin()
  {
    return "anonymous";
  }
}
?>
