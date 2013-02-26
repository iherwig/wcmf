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

use wcmf\lib\config\ConfigurationException;

use wcmf\lib\util\StringUtil;

/**
 * ObjectFactory loads class definitions and instantiates classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactory {

  /**
   * The registry for already created instances
   */
  private static $_instances = array();

  /**
   * Get the filename for a given class name. The method assumes that
   * namespaces are equivalent to directories.
   * @param className The fully qualified name of the class.
   * @return String
   */
  private static function getClassfile($className) {
    $classFile = WCMF_BASE.str_replace("\\", "/", $className).'.php';
    return $classFile;
  }

  /**
   * Load a class definition.
   * This method uses ObjectFactory::getClassfile for finding the class definition.
   * @param className The fully qualified name of the class.
   */
  private static function loadClassDefinition($className) {
    // find class file
    $classFile = self::getClassfile($className);

    // include class definition
    if (file_exists($classFile)) {
      require_once($classFile);
    }
    else {
      throw new ConfigurationException("Classfile ".$classFile." not found for classname: ".$className);
    }
  }

  /**
   * Get an instance from the configuration.
   * @param name The name of the instance (section, where the instance is defined)
   * @return Object
   */
  public static function getInstance($name) {
    $instance = null;

    // check if the instance is registered already
    if (!isset(self::$_instances[$name])) {
      // load class definition
      $configuration = self::getInstance('configuration');
      if (($instanceConfig = $configuration->getSection($name, false)) !== false) {
        $instance = self::createInstance($name, $instanceConfig);
      }
      else {
        throw new ConfigurationException($configuration->getErrorMsg());
      }
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
   * @param name The name of the instance.
   * @param instance The instance
   */
  public static function registerInstance($name, $instance) {
    self::$_instances[$name] = $instance;
  }

  /**
   * Delete all created instances.
   * @param keepConfiguration Boolean wether to keep the configuration or not [default: true]
   */
  public static function clear($keepConfiguration=true) {
    if ($keepConfiguration) {
      $configuration = self::getInstance('configuration');
    }
    self::$_instances = array();
    if ($keepConfiguration) {
      self::registerInstance('configuration', $configuration);
    }
  }

  /**
   * Get the setter method name for a property.
   * @param property
   * @return String
   */
  private static function getSetterName($property) {
    return 'set'.ucfirst(StringUtil::underScoreToCamelCase($property, true));
  }

  /**
   * Create an instance from the given configuration array.
   * @param name The name by which the instance may be retrieved later
   * using ObjectFactory::getInstance()
   * @param configuration Associative array with key value pairs for instance properties
   * and the special keys '__class' (optional, denotes the instance class name),
   * '__shared' (optional, if true, the instance may be reused using ObjectFactory::getInstance())
   * @return Object
   */
  public static function createInstance($name, $configuration) {
    $instance = null;

    if (isset($configuration['__class'])) {
      // the instance belongs to the given class
      $className = $configuration['__class'];

      // load the class definition
      self::loadClassDefinition($className);
      if (class_exists($className)) {
        // create the instance
        $obj = new $className;
        // set the instance properties
        foreach ($configuration as $key => $value) {
          // exclude properties starting with __
          if (strpos($key, '__') !== 0) {
            // replace variables denoted by a leading $
            if (strpos($value, '$') === 0) {
              $value = self::getInstance(preg_replace('/^\$/', '', $value));
            }
            // convert booleans
            if (is_string($value)) {
              $lower = strtolower($value);
              if ($lower === 'true') {
                $value = true;
              }
              if ($lower === 'false') {
                $value = false;
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
        throw new ConfigurationException("Class ".$className." is not found.");
      }
    }
    else {
      // the instance is a map
      foreach ($configuration as $key => $value) {
        // replace variables denoted by a leading $
        if (strpos($value, '$') === 0) {
          $configuration[$key] = self::getInstance(preg_replace('/^\$/', '', $value));
        }
      }
      // always register maps
      self::registerInstance($name, $configuration);
      $instance = $configuration;
    }
    return $instance;
  }
}
?>
