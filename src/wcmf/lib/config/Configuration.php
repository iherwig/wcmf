<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\config;

/**
 * Implementations of Configuration give access to the application
 * configuration. An instance of a Configuration implementation must
 * be created on application startup and must be registered at ObjectFactory
 * using the ObjectFactory::registerInstance() method.
 *
 * Configurations are supposed to be separated into sections, that contain
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
   * @param $name The name of the configuration
   * @param $processValues Boolean whether values should be processed after parsing (e.g. make arrays) (default: _true_)
   */
  public function addConfiguration($name, $processValues=true);

  /**
   * Get all section names.
   * @return An array of section names.
   */
  public function getSections();

  /**
   * Check if a section exists.
   * @param $section The section to check for.
   * @return Boolean
   */
  public function hasSection($section);

  /**
   * Get a section.
   * @param $section The section to return.
   * @param $includeMeta Boolean whether to include section meta data keys (optional, default: false)
   * @return Array holding the key/value pairs belonging to the section.
   * @throws ConfigurationException if the section does not exist
   */
  public function getSection($section, $includeMeta=false);

  /**
   * Check if a configuration value exists.
   * @param $key The name of the value.
   * @param $section The section the value belongs to.
   * @return Boolean
   */
  public function hasValue($key, $section);

  /**
   * Get a configuration value.
   * @param $key The name of the entry.
   * @param $section The section the key belongs to.
   * @return The configuration value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getValue($key, $section);

  /**
   * Get a value from the configuration as boolean if it represents a boolean.
   * @param $key The name of the entry.
   * @param $section The section the key belongs to.
   * @return Boolean or the original value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getBooleanValue($key, $section);

  /**
   * Get a directory value from the configuration.
   * The value will interpreted as directory relative to WCMF_BASE and
   * returned as absolute path.
   * @param $key The name of the entry.
   * @param $section The section the key belongs to.
   * @return The configuration value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getDirectoryValue($key, $section);

  /**
   * Get a file value from the configuration.
   * The value will interpreted as file relative to WCMF_BASE and
   * returned as absolute path.
   * @param $key The name of the entry.
   * @param $section The section the key belongs to.
   * @return The configuration value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getFileValue($key, $section);

  /**
   * Get a configuration key.
   * @param $value The value of the entry.
   * @param $section The section the value belongs to.
   * @return The configuration key.
   * @throws ConfigurationException if the key does not exist
   */
  public function getKey($value, $section);
}
?>
