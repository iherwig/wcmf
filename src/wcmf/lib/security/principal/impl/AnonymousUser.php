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
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\security\principal\AuthUser;
use wcmf\lib\security\principal\impl\DefaultAuthUser;

/**
 * Anonymous user
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AnonymousUser extends DefaultAuthUser implements AuthUser {

  /**
   * Constructor
   */
  public function __construct() {
    // all actions are forbidden if not stated otherwise
    $this->setDefaultPolicy(false);

    // parse policies
    $config = ObjectFactory::getConfigurationInstance();
    $policies = $config->getSection('authorization');
    $this->addPolicies($policies);
  }

  /**
   * @see AuthUser::login()
   */
  public function login($login, $password) {
    // do nothing
  }

  /**
   * @see AuthUser::login()
   */
  public function getLogin() {
    return "anonymous";
  }
}
?>
