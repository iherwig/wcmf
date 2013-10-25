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
 * using the ObjectFactory::configure() method.
 *
 * Configurations are supposed to be separated into section, that contain
 * keys with values. Section names and keys are treated case insensitive.
 *
 * There maybe more than one application configuration.
 * You can retrieve their names by using the Configuration::getConfigurations()
 * method. Different configurations maybe merged by calling the
 * Configuration::addConfiguration() method. While merging, existing values
 * will be overwritten, while new values will be added. This allows
 * to have very flexible application configurations for different scenarios,
 * users and roles.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Configuration {

  /**
   * Get a list of available configurations.
   * @return Array of configuration names.
   */
  public function getConfigurations();

  /**
   * Parses the given configuration and merges it with already added configurations.
   * @param name The name of the configuration
   * @param processValues Boolean whether values should be processed after parsing (e.g. make arrays) [default: true]
   */
  public function addConfiguration($name, $processValues=true);

  /**
   * Get all section names.
   * @return An array of section names.
   */
  public function getSections();

  /**
   * Check if a section exists.
   * @param section The section to check for.
   * @return Boolean
   */
  public function hasSection($section);

  /**
   * Get a section.
   * @param section The section to return.
   * @return Array holding the key/value pairs belonging to the section.
   * @throws ConfigurationException if the section does not exist
   */
  public function getSection($section);

  /**
   * Check if a configuration value exists.
   * @param key The name of the value.
   * @param section The section the value belongs to.
   * @return Boolean
   */
  public function hasValue($key, $section);

  /**
   * Get a configuration value.
   * @param key The name of the entry.
   * @param section The section the key belongs to.
   * @return The configuration value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getValue($key, $section);

  /**
   * Get a value from the configuration as boolean if it represents a boolean.
   * @param key The name of the entry.
   * @param section The section the key belongs to.
   * @return Boolean or the original value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getBooleanValue($key, $section);
}
?>
