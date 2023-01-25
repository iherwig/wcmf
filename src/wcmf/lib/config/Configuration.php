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
   * @return array<string> of configuration names.
   */
  public function getConfigurations(): array;

  /**
   * Parses the given configuration and merges it with already added configurations.
   * @param string $name The name of the configuration
   * @param bool $processValues Boolean whether values should be processed after parsing (e.g. make arrays) (default: _true_)
   */
  public function addConfiguration(string $name, bool $processValues=true): void;

  /**
   * Get all section names.
   * @return array<string>
   */
  public function getSections(): array;

  /**
   * Check if a section exists.
   * @param string $section The section to check for.
   * @return bool
   */
  public function hasSection(string $section): bool;

  /**
   * Get a section.
   * @param string $section The section to return.
   * @param bool $includeMeta Boolean whether to include section meta data keys (optional, default: false)
   * @return array<string, mixed>
   * @throws ConfigurationException if the section does not exist
   */
  public function getSection(string $section, bool $includeMeta=false): array;

  /**
   * Check if a configuration value exists.
   * @param string $key The name of the value.
   * @param string $section The section the value belongs to.
   * @return bool
   */
  public function hasValue(string $key, string $section): bool;

  /**
   * Get a configuration value.
   * @param string $key The name of the entry.
   * @param string $section The section the key belongs to.
   * @return mixed The configuration value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getValue(string $key, string $section);

  /**
   * Get a value from the configuration as boolean if it represents a boolean.
   * @param string $key The name of the entry.
   * @param string $section The section the key belongs to.
   * @return bool or the original value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getBooleanValue(string $key, string $section);

  /**
   * Get a directory value from the configuration.
   * The value will interpreted as directory relative to WCMF_BASE and
   * returned as absolute path.
   * @param string $key The name of the entry.
   * @param string $section The section the key belongs to.
   * @return array<string>|string The configuration value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getDirectoryValue(string $key, string $section);

  /**
   * Get a file value from the configuration.
   * The value will interpreted as file relative to WCMF_BASE and
   * returned as absolute path.
   * @param string $key The name of the entry.
   * @param string $section The section the key belongs to.
   * @return array<string>|string configuration value.
   * @throws ConfigurationException if the value does not exist
   */
  public function getFileValue(string $key, string $section);

  /**
   * Get a configuration key.
   * @param string $value The value of the entry.
   * @param string $section The section the value belongs to.
   * @return string configuration key.
   * @throws ConfigurationException if the key does not exist
   */
  public function getKey(string $value, string $section): string;
}
?>
