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
namespace wcmf\lib\core;

/**
 * Session is the interface for session implementations
 * and defines access to session variables.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Session {

  /**
   * Check if the session is started.
   * @return bool
   */
  public function isStarted(): bool;

  /**
   * Get the id of the session.
   * @return string
   */
  public function getID(): string;

  /**
   * Returns the value of a session variable
   * @param string $key The key (name) of the session vaiable.
   * @param mixed $default The default value if the key is not defined (optional, default: _null_)
   * @return mixed or null if it doesn't exist.
   */
  public function get(string $key, mixed $default=null): mixed;

  /**
   * Sets the value of a session variable.
   * @param string $key The key (name) of the session vaiable.
   * @param mixed $value The value of the session variable.
   */
  public function set(string $key, mixed $value): void;

  /**
   * Remove a session variable.
   * @param string $key The key (name) of the session variable.
   */
  public function remove(string $key): void;

  /**
   * Tests, if a certain session variable is defined.
   * @param string $key The key (name) of the session variable.
   * @return bool whether the session variable is set or not.
   */
  public function exist(string $key): bool;

  /**
   * Clear the session data.
   */
  public function clear(): void;

  /**
   * Destroy the session.
   */
  public function destroy(): void;

  /**
   * Set the login of authenticated user.
   * @param string $login Login name of the user
   */
  public function setAuthUser(string $login): void;

  /**
   * Get the login of the authenticated user.
   * @return string
   */
  public function getAuthUser(): string;
}