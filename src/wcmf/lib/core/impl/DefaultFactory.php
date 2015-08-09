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
namespace wcmf\lib\core\impl;

use wcmf\lib\config\Configuration;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\Factory;
use wcmf\lib\util\StringUtil;

/**
 * DefaultFactory loads class definitions and instantiates classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultFactory implements Factory {

  /**
   * Interfaces that must be implemented by the given instances used
   * in the framework.
   */
  protected $_requiredInterfaces = array(
      'eventManager' =>          'wcmf\lib\core\EventManager',
      'logger' =>                'wcmf\lib\core\Logger',
      'logManager' =>            'wcmf\lib\core\LogManager',
      'session' =>               'wcmf\lib\core\Session',
      'configuration' =>         'wcmf\lib\config\Configuration',
      'localization' =>          'wcmf\lib\i18n\Localization',
      'message' =>               'wcmf\lib\i18n\Message',
      'cache' =>                 'wcmf\lib\io\Cache',
      'persistenceFacade' =>     'wcmf\lib\persistence\PersistenceFacade',
      'transaction' =>           'wcmf\lib\persistence\Transaction',
      'concurrencyManager' =>    'wcmf\lib\persistence\concurrency\ConcurrencyManager',
      'actionMapper' =>          'wcmf\lib\presentation\ActionMapper',
      'request' =>               'wcmf\lib\presentation\Request',
      'response' =>              'wcmf\lib\presentation\Response',
      'listStrategies' =>        'wcmf\lib\presentation\control\lists\ListStrategy',
      'formats' =>               'wcmf\lib\presentation\format\Format',
      'view' =>                  'wcmf\lib\presentation\view\View',
      'authenticationManager' => 'wcmf\lib\security\AuthenticationManager',
      'permissionManager' =>     'wcmf\lib\security\PermissionManager',
      'principalFactory' =>      'wcmf\lib\security\principal\PrincipalFactory',
      'user' =>                  'wcmf\lib\security\principal\User',
      'role' =>                  'wcmf\lib\security\principal\Role',
  );

  /**
   * The registry for already created instances
   */
  protected $_instances = array();

  /**
   * The Configuration instance that holds the instance definitions
   */
  protected $_configuration = null;

  /**
   * Constructor.
   * @param $configuration Configuration instance used to construct instances.
   */
  public function __construct(Configuration $configuration) {
    // TODO get additional interfaces from configuration
    $this->_configuration = $configuration;
  }

  /**
   * @see Factory::getInstance()
   */
  public function getInstance($name, $dynamicConfiguration=array()) {
    $instance = null;

    // dynamic configuration must be included in internal instance key
    $instanceKey = sizeof($dynamicConfiguration) == 0 ? $name : $name.json_encode($dynamicConfiguration);

    // check if the instance is registered already
    if (!isset($this->_instances[$instanceKey])) {
      // get static instance configuration
      $staticConfiguration = $this->_configuration->getSection($name);
      $configuration = array_merge($staticConfiguration, $dynamicConfiguration);
      $instance = $this->createInstance($name, $configuration, $instanceKey);
    }
    else {
      $instance = $this->_instances[$instanceKey];
    }
    return $instance;
  }

  /**
   * @see Factory::getClassInstance()
   */
  public function getClassInstance($class, $dynamicConfiguration=array()) {
    $configuration = array_merge(array(
        '__class' => $class,
        '__shared' => false
    ), $dynamicConfiguration);

    $instance = $this->createInstance($class, $configuration, null);
    return $instance;
  }

  /**
   * @see Factory::registerInstance()
   */
  public function registerInstance($name, $instance) {
    $this->_instances[$name] = $instance;
  }

  /**
   * @see Factory::addInterfaces()
   */
  public function addInterfaces(array $interfaces) {
    $this->_requiredInterfaces = array_merge($this->_requiredInterfaces, $interfaces);
  }

  /**
   * @see Factory::clear()
   */
  public function clear() {
    $this->_instances = array();
  }

  /**
   * Get the setter method name for a property.
   * @param $property
   * @return String
   */
  protected function getSetterName($property) {
    return 'set'.ucfirst(StringUtil::underScoreToCamelCase($property, true));
  }

  /**
   * Create an instance from the given configuration array.
   * @param $name The name by which the instance may be retrieved later
   * using Factory::getInstance(). The name is also used to check the interface
   * (see Factory::addInterfaces())
   * @param $configuration Associative array with key value pairs for instance properties
   * passed to the constructor, setters or public properties and the special keys '__class'
   * (optional, denotes the instance class name), '__shared' (optional, if true, the
   * instance may be reused using Factory::getInstance())
   * @param $instanceKey The name under which the instance is registered for later retrieval
   * @return Object
   */
  protected function createInstance($name, $configuration, $instanceKey) {
    $instance = null;

    if (isset($configuration['__class'])) {
      // the instance belongs to the given class
      $className = $configuration['__class'];

      // class definition must be supplied by autoloader
      if (class_exists($className)) {

        // collect constructor parameters
        $cParams = array();
        $refClass = new \ReflectionClass($className);
        if ($refClass->hasMethod('__construct')) {
          $refConstructor = new \ReflectionMethod($className, '__construct');
          $refParameters = $refConstructor->getParameters();
          foreach ($refParameters as $param) {
            $paramName = $param->name;
            if (isset($this->_instances[$paramName])) {
              // parameter is already registered
              $cParams[$paramName] = $this->_instances[$paramName];
            }
            elseif (isset($configuration[$paramName])) {
              // parameter is explicitly defined in instance configuration
              $cParams[$paramName] = $this->resolveValue($configuration[$paramName]);
            }
            elseif ($this->_configuration->hasSection($paramName)) {
              // parameter exists in configuration
              $cParams[$paramName] = $this->getInstance($paramName);
            }
            elseif (($paramClass = $param->getClass()) != null) {
              // check for parameter's class from type hint
              // will cause an exception, if the class does not exist
              $cParams[$paramName] = $this->getClassInstance($paramClass->name);
            }
            else {
              throw new ConfigurationException('Constructor parameter \''.$paramName.
                      '\' in class \''.$className.'\' cannot be injected.');
            }
          }
        }

        // create the instance
        $obj = $refClass->newInstanceArgs($cParams);

        // check against interface
        $interface = $this->getInterface($name);
        if ($interface != null && !($obj instanceof $interface)) {
          throw new ConfigurationException('Class \''.$className.
                  '\' is required to implement interface \''.$interface.'\'.');
        }

        // register the instance if it is shared (default)
        // NOTE we do this before setting the instance properties in order
        // to allow to resolve circular dependencies (dependent objects that
        // are injected into the current instance via property injection can
        // already use this instance)
        if (!isset($configuration['__shared']) || $configuration['__shared'] == 'true') {
          $this->registerInstance($instanceKey, $obj);
        }

        // set the instance properties
        foreach ($configuration as $key => $value) {
          // exclude properties starting with __ and constructor parameters
          if (strpos($key, '__') !== 0 && !isset($cParams[$key])) {
            $value = $this->resolveValue($value);
            // set the property
            $setterName = $this->getSetterName($key);
            if (method_exists($obj, $setterName)) {
              $obj->$setterName($value);
            }
            else {
              $obj->$key = $value;
            }
          }
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
      $interface = $this->getInterface($name);

      foreach ($configuration as $key => $value) {
        // create instances for variables denoted by a leading $
        if (strpos($value, '$') === 0) {
          $obj = $this->getInstance(preg_replace('/^\$/', '', $value));
          // check against interface
          if ($interface != null && !($obj instanceof $interface)) {
            throw new ConfigurationException('Class of \''.$name.'.'.$key.
                    '\' is required to implement interface \''.$interface.'\'.');
          }
          $configuration[$key] = $obj;
        }
      }
      // always register maps
      $this->registerInstance($instanceKey, $configuration);
      $instance = $configuration;
    }
    return $instance;
  }

  /**
   * Resolve a configuration value into a parameter
   * @param $value
   * @return Mixed
   */
  protected function resolveValue($value) {
    // special treatments, if value is a string
    if (is_string($value)) {
      // replace variables denoted by a leading $
      if (strpos($value, '$') === 0) {
        $value = $this->getInstance(preg_replace('/^\$/', '', $value));
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
          $result[] = $this->getInstance(preg_replace('/^\$/', '', $val));
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
    return $value;
  }

  /**
   * Get the interface that is required to be implemented by the given instance
   * @param $name The name of the instance
   * @return Interface or null, if undefined
   */
  protected function getInterface($name) {
    if (isset($this->_requiredInterfaces[$name])) {
      return $this->_requiredInterfaces[$name];
    }
    return null;
  }
}
?>
