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
namespace wcmf\lib\config;

/**
 * Implementations of Configuration give access to the application
 * configuration. An instance of a Configuration implementation must
 * be created on application startup and must be registered at ObjectFactory
 * under the name 'configuration'.
 * Configurations are supposed to be separated into section, that contain
 * keys with values.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Configuration {

  /**
   * Get all section names.
   * @return An array of section names.
   */
  public function getSections();

  /**
   * Get a section.
   * @param section The section to return.
   * @param caseSensitive True/False, whether to look up the key case sensitive or not [default: true]
   * @return An assoziative array holding the key/value pairs belonging to the section or
   *         False if the section does not exist (use getErrorMsg() for detailed information).
   */
  public function getSection($section, $caseSensitive=true);

  /**
   * Get a value from the formerly parsed ini file.
   * @param key The name of the entry.
   * @param section The section the key belongs to.
   * @param caseSensitive True/False, whether to look up the key case sensitive or not [default: true]
   * @return The results of the parsed ini file or
   *         False in the case of wrong parameters (use getErrorMsg() for detailed information).
   */
  public function getValue($key, $section, $caseSensitive=true);

  /**
   * Get a value from the formerly parsed ini file as boolean if it represents
   * a boolean.
   * @param key The name of the entry.
   * @param section The section the key belongs to.
   * @param caseSensitive True/False, whether to look up the key case sensitive or not [default: true]
   * @return The results of the parsed ini file or
   *         False in the case of wrong parameters (use getErrorMsg() for detailed information).
   */
  public function getBooleanValue($key, $section, $caseSensitive=true);
}
?>
