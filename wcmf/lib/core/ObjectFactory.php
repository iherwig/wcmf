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

/**
 * ObjectFactory loads class definitions and instantiates classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactory {

  /**
   * Get the filename for a given class name. The method assumes that
   * namespaces are equivalent to directories.
   * @param className The fully qualified name of the class.
   * @return String
   */
  public static function getClassfile($className) {
    $classFile = WCMF_BASE.str_replace("\\", "/", $className).'.php';
    return $classFile;
  }

  /**
   * Load a class definition.
   * This method uses ObjectFactory::getClassfile for finding the class definition.
   * @param className The fully qualified name of the class.
   */
  public static function loadClassDefinition($className) {
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
   * Load a class definition from a configuration entry and return the class name.
   * The class name is looked up as value of $classEntry in $section of the main
   * configuration file. With this information it includes the desired class definition.
   * This method uses ObjectFactory::getClassfile for finding the class definition.
   * @param section The name of the section, where the class is defined.
   * @param classEntry The name of the key in the section, where the class is defined.
   * @return String
   */
  public static function loadClassDefinitionFromConfig($section, $classEntry) {
    // find class name
    $parser = InifileParser::getInstance();
    if (($className = $parser->getValue($classEntry, $section)) === false) {
      throw new ConfigurationException($parser->getErrorMsg());
    }
    // include class definition
    self::loadClassDefinition($className);
    return $className;
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
   * Create an object from a configuration entry.
   * This method looks up the class name as value of $classEntry in $section of the main
   * configuration file and takes - if given - constructor parameters from the 'initparams'
   * section and class definition found by ObjectFactory::getClassfile. With this information it
   * constructs the desired object.
   * @param section The name of the section, where the class is defined.
   * @param classEntry The name of the key in the section, where the class is defined.
   * @return Instance of the class.
   */
  public static function createInstanceFromConfig($section, $classEntry) {
    // load class definition
    $parser = InifileParser::getInstance();
    if (($className = $parser->getValue($classEntry, $section)) === false) {
      throw new ConfigurationException($parser->getErrorMsg());
    }
    return self::createInstance($className);
  }
}
?>
