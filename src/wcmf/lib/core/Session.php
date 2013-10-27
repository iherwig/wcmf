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
namespace wcmf\lib\core;

/**
 * Session is the interface for session implementations
 * and defines access to session variables.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Session {

  /**
   * Get the id of the session.
   * @return The id of the current session.
   */
  public function getID();

  /**
   * Returns the value of an session variable
   * @param key The key (name) of the session vaiable.
   * @return The session var or null if it doesn't exist.
   */
  public function get($key);

  /**
   * Sets the value of an session variable.
   * @param key The key (name) of the session vaiable.
   * @param value The value of the session variable.
   */
  public function set($key, $value);

  /**
   * Remove a session variable.
   * @param key The key (name) of the session variable.
   */
  public function remove($key);

  /**
   * Tests, if a certain session variable is defined.
   * @param key The key (name) of the session variable.
   * @return A boolean flag. true if the session variable is set, false if not.
   */
  public function exist($key);

  /**
   * Clear the session data.
   */
  public function clear();

  /**
   * Destroy the session.
   */
  public function destroy();

  /**
   * Add an error to the session data.
   * @param key The identifier of the error
   * @param error The error message
   */
  public function addError($key, $error);

  /**
   * Get an error stored in the session data.
   * @param key The identifier of the error
   * @return The error message
   */
  public function getError($key);

  /**
   * Get all errors stored in the session data.
   * @return The error message
   */
  public function getErrors();

  /**
   * Clear the session error data.
   */
  public function clearErrors();
}