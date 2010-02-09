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
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/util/class.FileUtil.php");

/**
 * @class WCMFInifileParser
 * @ingroup Presentation
 * @brief WCMFInifileParser adds methods for wcmf specific inifiles.
 *        This class is a decorator to the InifileParser class, showing only the
 *        readonly methods of InifileParser in it's interface.
 *        The advantage in using the InifileParser singleton inside
 *        this class is that its instance will hold the same configuration
 *        data as the WCMFInifileParser instance does.@n
 *        For this reason other classes may use the InifileParser instance
 *        not knowing about the WCMFInifileParser class at all.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class WCMFInifileParser
{
  private static $_instance = null;
  private $_actionDelimiter = '?';

  private function __construct() {}

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!is_object(self::$_instance))
    {
      self::$_instance = new WCMFInifileParser();
      // assign instance to InifileParser instance
      $baseInstance = InifileParser::getInstance();
      $baseInstance = &self::$_instance;
    }
    return self::$_instance;
  }
  /**
   * Get a list of available configuration files.
   * @return Array of configuration file names.
   * @note static method
   * The method relies on the following globally defined variables
   * - CONFIG_PATH: the path of the configuration files
   * - CONFIG_EXTENSION: the extension of configuration files (e.g. 'ini')
   */
  function getIniFiles()
  {
    global $CONFIG_PATH, $CONFIG_EXTENSION;
    $fileUtil = new FileUtil();
    return $fileUtil->getFiles($CONFIG_PATH, "/\.".$CONFIG_EXTENSION."$/", true);
  }
  /**
   * Get an ini file key that matches a given combination of resource, context, action best.
   * @param section The section to search in.
   * @param resource The given resource.
   * @param context The given context.
   * @param action The given action.
   * @return The best matching key or an empty string if nothing matches.
   */
  function getBestActionKey($section, $resource, $context, $action)
  {
    $parser = &InifileParser::getInstance();
    // check resource?context?action
    if (strlen($resource) > 0 && strlen($context) > 0 && strlen($action) > 0)
    {
    	$key = $resource.$this->_actionDelimiter.$context.$this->_actionDelimiter.$action;
      if ($parser->getValue($key, $section, false) !== false)
        return $key;
    }

    // check resource??action
    if (strlen($resource) > 0 && strlen($action) > 0)
    {
    	$key = $resource.$this->_actionDelimiter.$this->_actionDelimiter.$action;
      if ($parser->getValue($key, $section, false) !== false)
        return $key;
    }

    // check resource?context?
    if (strlen($resource) > 0 && strlen($context) > 0)
    {
    	$key = $resource.$this->_actionDelimiter.$context.$this->_actionDelimiter;
      if ($parser->getValue($key, $section, false) !== false)
        return $key;
    }

    // check ?context?action
    if (strlen($context) > 0 && strlen($action) > 0)
    {
    	$key = $this->_actionDelimiter.$context.$this->_actionDelimiter.$action;
      if ($parser->getValue($key, $section, false) !== false)
        return $key;
    }

    // check ??action
    if (strlen($action) > 0)
    {
    	$key = $this->_actionDelimiter.$this->_actionDelimiter.$action;
      if ($parser->getValue($key, $section, false) !== false)
        return $key;
     }

    // check resource??
    if (strlen($resource) > 0)
    {
    	$key = $resource.$this->_actionDelimiter.$this->_actionDelimiter;
      if ($parser->getValue($key, $section, false) !== false)
        return $key;
    }

    // check ?context?
    if (strlen($context) > 0)
    {
    	$key = $this->_actionDelimiter.$context.$this->_actionDelimiter;
      if ($parser->getValue($key, $section, false) !== false)
        return $key;
    }

    // check ??
    $key = $this->_actionDelimiter.$this->_actionDelimiter;
    if ($parser->getValue($key, $section, false) !== false)
      return $key;

    return '';
  }

  /**
   * Implement InifileParser public readonly Interface
   */

  /**
   * @see InifileParser::getErrorMsg()
   */
  function getErrorMsg()
  {
    $parser = &InifileParser::getInstance();
    return $parser->getErrorMsg();
  }
  /**
   * @see InifileParser::parseIniFile()
   */
  function parseIniFile($filename, $processValues=true)
  {
    $parser = &InifileParser::getInstance();
    $parser->parseIniFile($filename, $processValues);
  }
  /**
   * @see InifileParser::getData()
   */
  function getData()
  {
    $parser = &InifileParser::getInstance();
    return $parser->getData();
  }
  /**
   * @see InifileParser::getSections()
   */
  function getSections()
  {
    $parser = &InifileParser::getInstance();
    return $parser->getSections();
  }
  /**
   * @see InifileParser::getSection()
   */
  function getSection($section, $caseSensitive=true)
  {
    $parser = &InifileParser::getInstance();
    return $parser->getSection($section, $caseSensitive);
  }
  /**
   * @see InifileParser::getValue()
   */
  function getValue($key, $section, $caseSensitive=true)
  {
    $parser = &InifileParser::getInstance();
    return $parser->getValue($key, $section, $caseSensitive);
  }
}
?>
