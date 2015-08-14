<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\security\AuthenticationManager;
use wcmf\lib\security\principal\PrincipalFactory;

/**
 * DefaultAuthenticationManager uses PrincipalFactory to get a User instance
 * that matches the given login/password combination.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultAuthenticationManager implements AuthenticationManager {

  private $_principalFactory = null;

  /**
   * Constructor
   * @param $principalFactory PrincipalFactory instance
   */
  public function __construct(PrincipalFactory $principalFactory) {
    $this->_principalFactory = $principalFactory;
  }

  /**
   * @see AuthenticationManager::login()
   */
  public function login($login, $password) {
    $config = ObjectFactory::getInstance('configuration');

    // try to receive the user with given credentials
    $user = $this->_principalFactory->getUser($login, true);

    // check if user exists
    $loginOk = false;
    if ($user != null) {
      // check password
      $loginOk = $user->verifyPassword($password, $user->getPassword());
      if ($loginOk) {
        // load user config initially
        $userConfig = $user->getConfig();
        if (strlen($userConfig) > 0) {
          $config->addConfiguration($userConfig);
        }
        return $user;
      }
    }
    return null;
  }
}
?>
