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
namespace wcmf\lib\security\impl;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\security\AuthenticationManager;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\security\principal\User;

/**
 * DefaultAuthenticationManager uses PrincipalFactory to get a User instance
 * that matches the given login/password combination.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultAuthenticationManager implements AuthenticationManager {

  protected ?Configuration $configuration = null;
  protected ?PrincipalFactory $principalFactory = null;

  /**
   * Constructor
   * @param PersistenceFacade $persistenceFacade
   * @param Session $session
   * @param array<DynamicRole>
   */
  public function __construct(Configuration $configuration,
          PrincipalFactory $principalFactory) {
    $this->configuration = $configuration;
    $this->principalFactory = $principalFactory;
  }

  /**
   * @see AuthenticationManager::login()
   *
   * @param array{'login': string, 'password': string} $credentials
   */
  public function login(array $credentials): ?User {
    if (!isset($credentials['login']) || !isset($credentials['password'])) {
      throw new IllegalArgumentException("The parameters 'login' and 'password' are required");
    }

    $login = $credentials['login'];
    $password = $credentials['password'];

    // try to receive the user with given credentials
    $user = $this->principalFactory->getUser($login, true);

    // check if user exists
    $loginOk = false;
    if ($user != null && $user->isActive()) {
      // check password
      $loginOk = $user->verifyPassword($password);
      if ($loginOk) {
        // load user config initially
        $userConfig = $user->getConfig();
        if (strlen($userConfig) > 0) {
          $this->configuration->addConfiguration($userConfig);
        }
        return $user;
      }
    }
    return null;
  }
}
?>
