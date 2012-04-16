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
use wcmf\lib\config\InifileParser;

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
   * Create an instance of a class.
   * This method uses ObjectFactory::getClassfile for finding the class definition
   * and takes - if given - constructor parameters from the 'initparams'
   * section. With this information it constructs the desired object.
   * @param className The fully qualified name of the class.
   * @return Instance of the class.
   */
  public static function createInstance($className) {
    $obj = null;

    // load class definition
    self::loadClassDefinition($className);

    // find init parameters
    $initParams = null;
    $parser = InifileParser::getInstance();
    if (($initSection = $parser->getValue($className, 'initparams')) !== false) {
      if (($initParams = $parser->getSection($initSection)) === false) {
        $initParams = null;
      }
    }
    if ($initParams != null) {
      $obj = new $className($initParams);
    }
    else if (class_exists($className)) {
      $obj = new $className;
    }
    else {
      throw new ConfigurationException("Class ".$className." is not found.");
    }
    return $obj;
  }

  /**
   * Get an instance from the configuration.
   * @param name The name of the section, where the instance is defined.
   * @return Object.
   */
  public static function getInstance($name) {
    $instance = null;

    // check if the instance is registered already
    if (!isset(self::$_instances[$name])) {
      // load class definition
      $parser = InifileParser::getInstance();
      if (($configSection = $parser->getSection($name)) !== false) {
        if (isset($configSection['__class'])) {
          // the instance belongs to the given class
          $className = $configSection['__class'];

          // load the class definition
          self::loadClassDefinition($className);
          if (class_exists($className)) {
            // create the instance
            $obj = new $className;
            // set the instance properties
            foreach ($configSection as $key => $value) {
              // exclude properties starting with __
              if (strpos($value, '__') !== 0) {
                // replace variables denoted by a leading $
                if (strpos($value, '$') === 0) {
                  $value = self::getInstance(preg_replace('/^\$/', '', $value));
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
            // register the instance if it is shared
            if (!isset($configSection['__shared']) || $configSection['__shared'] == 'true') {
              self::$_instances[$name] = $obj;
            }
            $instance = $obj;
          }
          else {
            throw new ConfigurationException("Class ".$className." is not found.");
          }
        }
        else {
          // the instance is a map
          foreach ($configSection as $key => $value) {
            // replace variables denoted by a leading $
            if (strpos($value, '$') === 0) {
              $configSection[$key] = self::getInstance(preg_replace('/^\$/', '', $value));
            }
          }
          // always register maps
          self::$_instances[$name] = $configSection;
          $instance = $configSection;
        }
      }
      else {
        throw new ConfigurationException($parser->getErrorMsg());
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
   * Get the setter method name for a property.
   * @param property
   * @return String
   */
  private static function getSetterName($property) {
    return 'set'.ucfirst(StringUtil::underScoreToCamelCase($property, true));
  }
}
?>
