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
namespace wcmf\lib\core;

/**
 * Interface for Factory implementations. A Factory is used to instantiate
 * services to be used by clients.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Factory {

  /**
   * Get an instance from the configuration. Instances created with this method
   * might be shared (depending on the __shared configuration property).
   * @note Instance names are treated case insensitive
   * @param $name The name of the instance (section, where the instance is defined)
   * @param $dynamicConfiguration Associative array with key value pairs for
   * dynamic instance properties (optional)
   * @return Object
   */
  public function getInstance($name, $dynamicConfiguration=array());

  /**
   * Get a new instance from the configuration. Instances created with this method are not shared.
   * @note Instance names are treated case insensitive
   * @param $name The name of the instance (section, where the instance is defined)
   * @param $dynamicConfiguration Associative array with key value pairs for
   * dynamic instance properties (optional)
   * @return Object
   */
  public function getNewInstance($name, $dynamicConfiguration=array());

  /**
   * Create an instance of a class. Instances created with this method are not shared.
   * @param $class The name of the class
   * @param $dynamicConfiguration Associative array with key value pairs for
   * dynamic instance properties (optional)
   * @return Object
   */
  public function getInstanceOf($class, $dynamicConfiguration=array());

  /**
   * Register a shared instance with a given name.
   * @note Instance names are treated case insensitive
   * @param $name The name of the instance.
   * @param $instance The instance
   */
  public function registerInstance($name, $instance);

  /**
   * Add interfaces that instances must implement.
   * @param $interfaces Associative array with instance names as keys and interface names as values.
   */
  public function addInterfaces(array $interfaces);

  /**
   * Delete all created instances.
   */
  public function clear();
}
?>
