<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\security\principal\User;

/**
 * Anonymous user
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AnonymousUser implements User {

  const USER_GROUP_NAME = 'anonymous';

  private $config = null;

  /**
   * @see User::getOID()
   */
  public function getOID() {
    return null;
  }

  /**
   * @see User::setLogin()
   */
  public function setLogin($login) {}

  /**
   * @see User::getLogin()
   */
  public function getLogin() {
    return self::USER_GROUP_NAME;
  }

  /**
   * @see User::setPassword()
   */
  public function setPassword($password) {}

  /**
   * @see User::getPassword()
   */
  public function getPassword() {
    return null;
  }

  /**
   * @see User::verifyPassword()
   */
  public function verifyPassword($password) {
    return false;
  }

  /**
   * @see User::setIsActive()
   */
  public function setIsActive($isActive) {}

  /**
   * @see User::isActive()
   */
  public function isActive() {
    return true;
  }

  /**
   * @see User::setIsSuperUser()
   */
  public function setIsSuperUser($isSuperUser) {}

  /**
   * @see User::isSuperUser()
   */
  public function isSuperUser() {
    return false;
  }

  /**
   * @see User::setConfig()
   */
  public function setConfig($config) {
    $this->config = $config;
  }

  /**
   * @see User::getConfig()
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * @see User::hasRole()
   */
  public function hasRole($rolename) {
    return $rolename == self::USER_GROUP_NAME;
  }

  /**
   * @see User::getRoles()
   */
  public function getRoles() {
    return array(self::USER_GROUP_NAME);
  }
}
?>
