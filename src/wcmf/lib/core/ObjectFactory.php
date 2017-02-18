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

use wcmf\lib\config\ConfigurationException;

/**
 * ObjectFactory implements the _service locator_ pattern by wrapping a
 * Factory instance and providing static access to it.
 *
 * It delegates the work of actually instantiating services to the configured
 * Factory instance.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactory {

  private static $factory = null;

  /**
   * Configure the factory.
   * @param $factory Factory instance that actually does the instantiation.
   */
  public static function configure(Factory $factory) {
    self::$factory = $factory;
  }

  /**
   * Check if the factory is configured.
   * @return Boolean
   */
  public static function isConfigured() {
    return self::$factory != null;
  }

  /**
   * @see Factory::getInstance()
   */
  public static function getInstance($name, $dynamicConfiguration=[]) {
    self::checkConfig();
    return self::$factory->getInstance($name, $dynamicConfiguration);
  }

  /**
   * @see Factory::getNewInstance()
   */
  public static function getNewInstance($name, $dynamicConfiguration=[]) {
    self::checkConfig();
    return self::$factory->getNewInstance($name, $dynamicConfiguration);
  }

  /**
   * @see Factory::getInstanceOf()
   */
  public static function getInstanceOf($class, $dynamicConfiguration=[]) {
    self::checkConfig();
    return self::$factory->getInstanceOf($class, $dynamicConfiguration);
  }

  /**
   * @see Factory::registerInstance()
   */
  public static function registerInstance($name, $instance) {
    self::checkConfig();
    self::$factory->registerInstance($name, $instance);
  }

  /**
   * @see Factory::addInterfaces()
   */
  public function addInterfaces($interfaces) {
    self::checkConfig();
    self::$factory->addInterfaces($interfaces);
  }

  /**
   * @see Factory::clear()
   */
  public static function clear() {
    if (self::$factory != null) {
      self::$factory->clear();
    }
    self::$factory = null;
  }

  /**
   * Check if the configuration is valid.
   */
  private static function checkConfig() {
    if (self::$factory == null) {
      throw new ConfigurationException('No Factory instance provided. Do this by calling the configure() method.');
    }
  }
}
?>