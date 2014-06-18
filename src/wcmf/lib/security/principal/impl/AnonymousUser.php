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
