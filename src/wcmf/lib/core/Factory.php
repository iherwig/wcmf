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
 * Interface for Factory implementations. A Factory is used to instantiate
 * services to be used by clients.
 *
 * The factory gets instance definitions from the application configuration which
 * is organized in sections. Each section has a name (the instance name) and consists
 * of key value pairs that are used to initialize the instance. If the map contains
 * a __class key, it's value defines the class of the instance and the remaining
 * keys denote properties of the class. If the map does not contain a __class key,
 * the instance will be an associative array. Values starting with $ will be resolved
 * as instances, if the remaining value denotes a configuration section.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Factory {

  /**
   * Get an instance from the configuration. Instances created with this method
   * might be shared (depending on the __shared configuration property).
   * @note Instance names are treated case insensitive
   * @param string $name The name of the instance (section, where the instance is defined)
   * @param array $dynamicConfiguration Array with key value pairs for
   * dynamic instance properties (optional)
   * @return object|array
   */
  public function getInstance(string $name, array $dynamicConfiguration=[]): object|array;

  /**
   * Get a new instance from the configuration. Instances created with this method are not shared.
   * @note Instance names are treated case insensitive
   * @param string $name The name of the instance (section, where the instance is defined)
   * @param array $dynamicConfiguration Array with key value pairs for
   * dynamic instance properties (optional)
   * @return object|array
   */
  public function getNewInstance(string $name, array $dynamicConfiguration=[]): object|array;

  /**
   * Create an instance of a class. Instances created with this method are not shared.
   * @param string $class The name of the class
   * @param array $dynamicConfiguration Array with key value pairs for
   * dynamic instance properties (optional)
   * @return object|array
   */
  public function getInstanceOf(string $class, array $dynamicConfiguration=[]): object|array;

  /**
   * Register a shared instance with a given name.
   * @note Instance names are treated case insensitive
   * @param string $name The name of the instance.
   * @param object|array $instance The instance (object or array)
   */
  public function registerInstance(string $name, object|array $instance): void;

  /**
   * Add interfaces that instances must implement.
   * @param array $interfaces Array with instance names as keys and interface names as values.
   */
  public function addInterfaces(array $interfaces): void;

  /**
   * Delete all created instances.
   */
  public function clear(): void;
}
?>
