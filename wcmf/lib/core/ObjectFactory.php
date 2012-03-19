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
require_once(WCMF_BASE."wcmf/lib/core/ConfigurationException.php");
require_once(WCMF_BASE."wcmf/lib/util/InifileParser.php");

/**
 * @class ObjectFactory
 * @ingroup Util
 * @brief ObjectFactory loads class definitions and instantiates classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactory
{
  /**
   * Get the class filename for a given class name.
   * This method looks up the class definition from 'classmapping' section.
   * @param className The name of the class.
   * @return The class filename
   */
  public static function getClassfile($className)
  {
    // find class file
    $parser = InifileParser::getInstance();
    if (($classFile = $parser->getValue($className, 'classmapping', false)) === false) {
      throw new ConfigurationException($parser->getErrorMsg());
    }
    return $classFile;
  }
  /**
   * Load a class definition from the 'classmapping' section.
   * This method looks up the class name as key in the 'classmapping' section of the main
   * configuration file and includes the desired class definition.
   * @param className The name of the key in the 'classmapping' section.
   */
  public static function loadClassDefinition($className)
  {
    // find class file
    $classFile = self::getClassfile($className);

    // include class definition
    if (file_exists(WCMF_BASE.$classFile)) {
      require_once(WCMF_BASE.$classFile);
    }
    else {
      throw new ConfigurationException("Classfile ".$classFile." not found.");
    }
  }
  /**
   * Load a class definition from a configuration entry.
   * This method looks up the class name as value of $classEntry in $section of the main
   * configuration file and takes class definition from 'classmapping' section.
   * With this information it includes the desired class definition.
   * @param section The name of the section, where the class is defined.
   * @param classEntry The name of the key in the section, where the class is defined.
   * @return The classname
   */
  public static function loadClassDefinitionFromConfig($section, $classEntry)
  {
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
   * Create an object from a classmapping entry.
   * This method looks up the class name as key in the 'classmapping' section of the main
   * configuration file and takes - if given - constructor parameters from the 'initparams'
   * section. With this information it constructs the desired object.
   * @param className The name of the key in the 'classmapping' section.
   * @return A reference to an instance of the class.
   */
  public static function createInstance($className)
  {
    $obj = null;

    // load class definition
    self::loadClassDefinition($className);
    
    // find init parameters
    $initParams = null;
    $parser = InifileParser::getInstance();
    if (($initSection = $parser->getValue($className, 'initparams')) !== false)
    {
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
      throw new ConfigurationException("Class ".$className." is not found defined.");
    }
    return $obj;
  }
  /**
   * Create an object from a configuration entry.
   * This method looks up the class name as value of $classEntry in $section of the main
   * configuration file and takes - if given - constructor parameters from the 'initparams'
   * section and class definition from 'classmapping' section. With this information it
   * constructs the desired object.
   * @param section The name of the section, where the class is defined.
   * @param classEntry The name of the key in the section, where the class is defined.
   * @return A reference to an instance of the class.
   */
  public static function createInstanceFromConfig($section, $classEntry)
  {
    // load class definition
    $parser = InifileParser::getInstance();
    if (($className = $parser->getValue($classEntry, $section)) === false) {
      throw new ConfigurationException($parser->getErrorMsg());
    }
    return self::createInstance($className);
  }
}
?>
