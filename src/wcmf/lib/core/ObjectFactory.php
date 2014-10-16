<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\core;

use wcmf\lib\config\Configuration;
use wcmf\lib\config\ConfigurationException;

use wcmf\lib\util\StringUtil;

/**
 * ObjectFactory loads class definitions and instantiates classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactory {

  /**
   * Interfaces that must be implemented by the given instances used
   * in the framework.
   */
  private static $_requiredInterfaces = array(
      'eventManager' =>          'wcmf\lib\core\EventManager',
      'session' =>               'wcmf\lib\core\Session',
      'configuration' =>         'wcmf\lib\config\Configuration',
      'localization' =>          'wcmf\lib\i18n\Localization',
      'cache' =>                 'wcmf\lib\io\Cache',
      'persistenceFacade' =>     'wcmf\lib\persistence\PersistenceFacade',
      'transaction' =>           'wcmf\lib\persistence\Transaction',
      'concurrencyManager' =>    'wcmf\lib\persistence\concurrency\ConcurrencyManager',
      'actionMapper' =>          'wcmf\lib\presentation\ActionMapper',
      'view' =>                  'wcmf\lib\presentation\view\View',
      'authenticationManager' => 'wcmf\lib\security\AuthenticationManager',
      'permissionManager' =>     'wcmf\lib\security\PermissionManager',
      'principalFactory' =>      'wcmf\lib\security\principal\PrincipalFactory',
      'user' =>                  'wcmf\lib\security\principal\User',
      'role' =>                  'wcmf\lib\security\principal\Role',

      'formats' =>               'wcmf\lib\presentation\format\Format',
      'listStrategies' =>        'wcmf\lib\presentation\control\lists\ListStrategy'
  );

  /**
   * The registry for already created instances
   */
  private static $_instances = array();

  /**
   * The Configuration instance that holds the instance definitions
   */
  private static $_configuration = null;

  /**
   * Set the Configuration instance.
   * @param $configuration Configuration instance used to construct instances.
   */
  public static function configure(Configuration $configuration) {
    self::$_configuration = $configuration;
  }

  /**
   * Get the Configuration instance.
   * @return Configuration
   */
  public static function getConfigurationInstance() {
    if (self::$_configuration == null) {
      throw new ConfigurationException('No Configuration instance provided. Do this by calling the configure() method.');
    }
    return self::$_configuration;
  }

  /**
   * Get an instance from the configuration.
   * @param $name The name of the instance (section, where the instance is defined)
   * @return Object
   */
  public static function getInstance($name) {
    $instance = null;

    // check if the instance is registered already
    if (!isset(self::$_instances[$name])) {
      if (self::$_configuration == null) {
        throw new ConfigurationException('No Configuration instance provided. Do this by calling the configure() method.');
      }
      // get instance configuration
      $instanceConfig = self::$_configuration->getSection($name);
      $instance = self::createInstance($name, $instanceConfig);
    }
    else {
      $instance = self::$_instances[$name];
    }
    return $instance;
  }

  /**
   * Get the names of the created shared instances.
   * @return Array
   */
  public static function getInstanceNames() {
    return array_keys(self::$_instances);
  }

  /**
   * Register a shared instance with a given name.
   * @param $name The name of the instance.
   * @param $instance The instance
   */
  public static function registerInstance($name, $instance) {
    self::$_instances[$name] = $instance;
  }

  /**
   * Delete all created instances.
   */
  public static function clear() {
    self::$_instances = array();
  }

  /**
   * Get the setter method name for a property.
   * @param $property
   * @return String
   */
  private static function getSetterName($property) {
    return 'set'.ucfirst(StringUtil::underScoreToCamelCase($property, true));
  }

  /**
   * Create an instance from the given configuration array.
   * @param $name The name by which the instance may be retrieved later
   * using ObjectFactory::getInstance()
   * @param $configuration Associative array with key value pairs for instance properties
   * and the special keys '__class' (optional, denotes the instance class name),
   * '__shared' (optional, if true, the instance may be reused using ObjectFactory::getInstance())
   * @return Object
   */
  public static function createInstance($name, $configuration) {
    $instance = null;

    if (isset($configuration['__class'])) {
      // the instance belongs to the given class
      $className = $configuration['__class'];

      // class definition must be supplied by autoloader
      if (class_exists($className)) {
        // create the instance
        $obj = new $className;
        // check against interface
        $interface = self::getInterface($name);
        if ($interface != null && !($obj instanceof $interface)) {
          throw new ConfigurationException('Class \''.$className.
                  '\' is required to implement interface \''.$interface.'\'.');
        }
        // set the instance properties
        foreach ($configuration as $key => $value) {
          // exclude properties starting with __
          if (strpos($key, '__') !== 0) {
            // special treatments, if value is a string
            if (is_string($value)) {
              // replace variables denoted by a leading $
              if (strpos($value, '$') === 0) {
                $value = self::getInstance(preg_replace('/^\$/', '', $value));
              }
              else {
                // convert booleans
                $lower = strtolower($value);
                if ($lower === 'true') {
                  $value = true;
                }
                if ($lower === 'false') {
                  $value = false;
                }
              }
            }
            // special treatments, if value is an array
            if (is_array($value)) {
              $result = array();
              $containsInstance = false;
              // check for variables
              foreach ($value as $val) {
                if (is_string($val) && strpos($val, '$') === 0) {
                  $result[] = self::getInstance(preg_replace('/^\$/', '', $val));
                  $containsInstance = true;
                }
                else {
                  $result[] = $val;
                }
              }
              // only replace value, if the array containes an variable
              if ($containsInstance) {
                $value = $result;
              }
            }
            // set the property
            $setterName = self::getSetterName($key);
            if (method_exists($obj, $setterName)) {
              $obj->$setterName($value);
            }
            else {
              $obj->$key = $value;
            }
          }
        }
        // register the instance if it is shared (default)
        if (!isset($configuration['__shared']) || $configuration['__shared'] == 'true') {
          self::registerInstance($name, $obj);
        }
        $instance = $obj;
      }
      else {
        throw new ConfigurationException('Class \''.$className.'\' is not found.');
      }
    }
    else {
      // the instance is a map

      // get interface that map values should implement
      $interface = self::getInterface($name);

      foreach ($configuration as $key => $value) {
        // create instances for variables denoted by a leading $
        if (strpos($value, '$') === 0) {
          $obj = self::getInstance(preg_replace('/^\$/', '', $value));
          // check against interface
          if ($interface != null && !($obj instanceof $interface)) {
            throw new ConfigurationException('Class of \''.$name.'.'.$key.
                    '\' is required to implement interface \''.$interface.'\'.');
          }
          $configuration[$key] = $obj;
        }
      }
      // always register maps
      self::registerInstance($name, $configuration);
      $instance = $configuration;
    }
    return $instance;
  }

  /**
   * Get the interface that is required to be implemented by the given instance
   * @param $name The name of the instance
   * @return Interface or null, if undefined
   */
  protected static function getInterface($name) {
    if (isset(self::$_requiredInterfaces[$name])) {
      return self::$_requiredInterfaces[$name];
    }
    return null;
  }
}
?>
