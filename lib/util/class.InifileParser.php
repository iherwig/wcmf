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
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/util/class.ArrayUtil.php");

/**
 * @class InifileParser
 * @ingroup Util
 * @brief InifileParser provides basic services for parsing a ini file from the file system.
 * @note This class only supports ino files with sections.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InifileParser
{
  private static $_instance = null;
  private $_errorMsg = '';
  private $_filename = null;
  private $_iniArray = array(); // an assoziate array that holds sections with keys with values
  private $_comments = array(); // an assoziate array that holds the comments/blank lines in the file
                            // (each comment is attached to the following section/key)
                            // the key ';' holds the comments at the end of the file
  private $_isModified = false;

  private function __construct() {}

  /**
   * InifileParser public readonly Interface
   */

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!is_object(self::$_instance)) {
      self::$_instance = new InifileParser();
    }
    return self::$_instance;
  }

  /**
   * Returns the errorMsg.
   * @return The error message.
   */
  function getErrorMsg()
  {
    return $this->_errorMsg;
  }

  /**
   * Check if file is modified.
   * @return True/False whether modified.
   */
  public function isModified()
  {
    return $this->_isModified;
  }

  /**
   * Parses a ini file and puts an array with all the key-values pairs into the object.
   * @param filename The filename of the ini file to parse
   * @param processValues True/False whether values should be processed after parsing (e.g. make arrays) [default: true]
   * @note ini files referenced in section 'config' key 'include' are parsed afterwards
   */
  public function parseIniFile($filename, $processValues=true)
  {
    global $CONFIG_PATH;
    if (file_exists($filename))
    {
      $this->_filename = $filename;
      // merge new with old values, overwrite redefined values
      $this->_iniArray = $this->configMerge($this->_iniArray, $this->_parse_ini_file($filename, true), true);

      // merge referenced ini files, don't override values
      if (($includes = $this->getValue('include', 'config')) !== false)
      {
        if (!is_array($includes)) {
          $includes = $this->processValue('include', 'config');
        }
        foreach($includes as $include) {
          $this->_iniArray = $this->configMerge($this->_iniArray, $this->_parse_ini_file($CONFIG_PATH.$include, true), false);
        }
      }
      if ($processValues) {
        $this->processValues();
      }
    }
    else {
      throw new IllegalArgumentException("Configuration file ".$filename." not found!");
    }
  }

  /**
   * Returns the data of the formerly parsed ini file.
   * @return The data of the parsed ini file.
   */
  public function getData()
  {
    return $this->_iniArray;
  }

  /**
   * Get all section names.
   * @return An array of section names.
   */
  public function getSections()
  {
    return array_keys($this->_iniArray);
  }

  /**
   * Get a section.
   * @param section The section to return.
   * @param caseSensitive True/False, whether to look up the key case sensitive or not [default: true]
   * @return An assoziative array holding the key/value pairs belonging to the section or
   *         False if the section does not exist (use getErrorMsg() for detailed information).
   */
  public function getSection($section, $caseSensitive=true)
  {
    if (!$caseSensitive)
    {
      $matchingKeys = ArrayUtil::get_matching_values_i($section, array_keys($this->_iniArray));
      if (sizeof($matchingKeys) > 0) {
        $section = array_pop($matchingKeys);
      }
    }

    if (!in_array($section, array_keys($this->_iniArray)))
    {
  	  $this->_errorMsg = "Section '".$section."' not found!";
      return false;
    }
  	else {
  	  return $this->_iniArray[$section];
  	}
  }

  /**
   * Get a value from the formerly parsed ini file.
   * @param key The name of the entry.
   * @param section The section the key belongs to.
   * @param caseSensitive True/False, whether to look up the key case sensitive or not [default: true]
   * @return The results of the parsed ini file or
   *         False in the case of wrong parameters (use getErrorMsg() for detailed information).
   */
  public function getValue($key, $section, $caseSensitive=true)
  {
    if ($this->getSection($section, $caseSensitive) === false) {
      return false;
    }
    $sectionArray = $this->getSection($section, $caseSensitive);
    if (!$caseSensitive)
    {
      $matchingKeys = ArrayUtil::get_matching_values_i($key, array_keys($sectionArray));
      if (sizeof($matchingKeys) > 0) {
        $key = array_pop($matchingKeys);
      }
    }
    if (!in_array($key, array_keys($sectionArray)))
    {
  	  $this->_errorMsg = "Key '".$key."' not found in section '".$section."'!";
      return false;
  	}
  	else {
      return $sectionArray[$key];
  	}
  }

  /**
   * InifileParser public modification Interface
   */

  /**
   * Set ini file data.
   * @param data The ini file data.
   */
  public function setData($data)
  {
    $this->_iniArray = $data;
    $this->_isModified = true;
  }

  /**
   * Check if a section is hidden.
   * @param section The name of the section.
   * @note hidden sections are defined as an array in section 'config' key 'hiddenSections'
   */
  public function isHidden($section)
  {
    $hiddenSections = $this->processValue('hiddenSections', 'config');
    if (is_array($hiddenSections) && in_array($section, $hiddenSections)) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Check if a section is editable.
   * @param section The name of the section.
   * @note readyonly sections are defined as an array in section 'config' key 'readonlySections'
   */
  public function isEditable($section)
  {
    $readonlySections = $this->processValue('readonlySections', 'config');
    if (is_array($readonlySections) && in_array($section, $readonlySections)) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * Create a section.
   * @param section The name of the section (will be trimmed).
   * @return True/false whether successful.
   */
  public function createSection($section)
  {
    $section = trim($section);
    if ($this->getSection($section) !== false)
    {
  	  $this->_errorMsg = "Section '".$section."' already exists!";
      return false;
    }
    if ($section == '')
    {
  	  $this->_errorMsg = "Empty section names are not allowed!";
      return false;
    }
    $this->_iniArray[$section] = '';
    $this->_isModified = true;
    return true;
  }

  /**
   * Remove a section.
   * @param section The name of the section.
   * @return True/false whether successful.
   */
  public function removeSection($section)
  {
    if (!$this->isEditable($section))
    {
  	  $this->_errorMsg = "Section ".$section." is not editable!";
      return false;
    }
    if ($this->getSection($section) === false) {
      return false;
    }
    unset($this->_iniArray[$section]);
    $this->_isModified = true;
    return true;
  }

  /**
   * Rename a section.
   * @param oldname The name of the section.
   * @param newname The new name of the section (will be trimmed).
   * @return True/false whether successful.
   */
  public function renameSection($oldname, $newname)
  {
    if (!$this->isEditable($oldname))
    {
  	  $this->_errorMsg = "Section ".$oldname." is not editable!";
      return false;
    }
    $newname = trim($newname);
    if ($this->getSection($oldname) === false) {
      return false;
    }
    if ($this->getSection($newname) !== false)
    {
  	  $this->_errorMsg = "Section '".$newname."' already exists!";
      return false;
    }
    if ($newname == '')
    {
  	  $this->_errorMsg = "Empty section names are not allowed!";
      return false;
    }
    ArrayUtil::key_array_rename($this->_iniArray, $oldname, $newname);
    $this->_isModified = true;
    return true;
  }

  /**
   * Create a key/value pair in a section.
   * @param key The name of the key (will be trimmed).
   * @param value The value of the key.
   * @param section The name of the section.
   * @param createSection The name of the section.
   * @return True/False whether successful.
   */
  public function setValue($key, $value, $section, $createSection=true)
  {
    if (!$this->isEditable($section))
    {
  	  $this->_errorMsg = "Section ".$section." is not editable!";
      return false;
    }
    $key = trim($key);
    if (!$createSection && ($this->getSection($section) === false)) {
      return false;
    }
    if ($key == '')
    {
  	  $this->_errorMsg = "Empty key names are not allowed!";
      return false;
    }
    $this->_iniArray[$section][$key] = $value;
    $this->_isModified = true;
    return true;
  }

  /**
   * Remove a key from a section.
   * @param key The name of the key.
   * @param section The name of the section.
   * @return True/False whether successful.
   */
  public function removeKey($key, $section)
  {
    if (!$this->isEditable($section))
    {
  	  $this->_errorMsg = "Section ".$section." is not editable!";
      return false;
    }
    if ($this->getValue($key, $section) === false) {
      return false;
    }
    unset($this->_iniArray[$section][$key]);
    $this->_isModified = true;
    return true;
  }

  /**
   * Rename a key in a section.
   * @param oldname The name of the section.
   * @param newname The new name of the section (will be trimmed).
   * @param section The name of the section.
   * @return True/false whether successful.
   */
  public function renameKey($oldname, $newname, $section)
  {
    if (!$this->isEditable($section))
    {
  	  $this->_errorMsg = "Section ".$section." is not editable!";
      return false;
    }
    $newname = trim($newname);
    if ($this->getValue($oldname, $section) === false) {
      return false;
    }
    if ($this->getValue($newname, $section) !== false)
    {
  	  $this->_errorMsg = "Key '".$newname."' already exists in section '".$section."'!";
      return false;
    }
    if ($newname == '')
    {
  	  $this->_errorMsg = "Empty key names are not allowed!";
      return false;
    }
    ArrayUtil::key_array_rename($this->_iniArray[$section], $oldname, $newname);
    $this->_isModified = true;
    return true;
  }

  /**
   * Write the ini data to a file.
   * @param filename The filename to write to, if null the original file will be used [default: null].
   * @return True/False whether successful
   */
  public function writeIniFile($filename=null)
  {
    if ($filename == null) {
      $filename = $this->_filename;
    }
    $content = "";
    foreach($this->_iniArray as $section => $values)
    {
      $sectionString = "[".$section."]";
      $content .= $this->_comments[$sectionString];
      $content .= $sectionString."\n";
      if (is_array($values))
      {
        foreach($values as $key => $value)
        {
          if (is_array($value)) {
            $value = "{".join(", ", $value)."}";
          }
          // unescape double quotes
          $value = str_replace("\\\"", "\"", $value);
          $content .= $this->_comments[$section][$key];
          $content .= $key." = ".$value."\n";
        }
      }
    }
    $content .= $this->_comments[';'];

    if (!$fh = fopen($filename, 'w'))
    {
  	  $this->_errorMsg = "Can't open ini file '".$filename."'!";
      return false;
    }

    if (!fwrite($fh, $content))
    {
  	  $this->_errorMsg = "Can't write ini file '".$filename."'!";
      return false;
    }
    fclose($fh);
    $this->_isModified = false;
    return true;
  }

  /**
   * InifileParser private Interface
   */

  /**
   * Load in the ini file specified in filename, and return
   * the settings in a multidimensional array, with the section names and
   * settings included.
   * @param filename The filename of the ini file to parse
   * @return An associative array containing the data
   *
   * @author: Sebastien Cevey <seb@cine7.net>
   *          Original Code base: <info@megaman.nl>
   *          Added comment handling/Removed process sections flag: Ingo Herwig
   */
  protected function _parse_ini_file($filename)
  {
    if (!file_exists($filename)) {
      throw new ConfigurationException("The config file ".$filename." does not exist.");
    }
    $ini_array = array();
    $sec_name = "";
    $lines = file($filename);
    $commentsPending = "";
    foreach($lines as $line)
    {
      $line = trim($line);
      // comments/blank lines
      if($line == "" || $line[0] == ";")
      {
        $commentsPending .= $line."\n";
        continue;
      }

      if($line[0] == "[" && $line[strlen($line)-1] == "]")
      {
        $sec_name = substr($line, 1, strlen($line)-2);
        $ini_array[$sec_name] = array();

        // store comments/blank lines for section
        $this->_comments[$line] = $commentsPending;
        $commentsPending = "";
      }
      else
      {
        $pos = strpos($line, "=");
        $property = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos+1));

        $ini_array[$sec_name][$property] = $value;

        // store comments/blank lines for key
        $this->_comments[$sec_name][$property] = $commentsPending;
        $commentsPending = "";
      }
    }
    // store comments/blank lines from the end of the file
    $this->_comments[';'] = substr($commentsPending, 0, -1);

    return $ini_array;
  }

  /**
   * Process the values in the ini array.
   * This method turns string values that hold array definitions
   * (comma separated values enclosed by curly brackets) into array values.
   * @attention Internal use only.
   */
  protected function processValues()
  {
    foreach(array_keys($this->_iniArray) as $section)
    {
      foreach(array_keys($this->_iniArray[$section]) as $key) {
        $this->_iniArray[$section][$key] = $this->processValue($key, $section);
      }
    }
  }

  /**
   * Process the values in the ini array.
   * This method turns string values that hold array definitions
   * (comma separated values enclosed by curly brackets) into array values.
   * @param key The key to process
   * @param section The section that holds the key
   * @return The processed value (Array or String)
   * @attention Internal use only.
   */
  protected function processValue($key, $section)
  {
    if (!array_key_exists($section, $this->_iniArray) ||
      !array_key_exists($key, $this->_iniArray[$section])) {
      return;
    }
    $value = $this->_iniArray[$section][$key];
    if (!is_array($value))
    {
      // decode encoded (%##) values
      if (preg_match ("/%/", $value)) {
        $value = urldecode($value);
      }
      // make arrays
      if(preg_match("/^{.*}$/", $value))
      {
        $arrayValues = StringUtil::quotesplit(substr($value, 1, -1));
        $value = array();
        foreach ($arrayValues as $arrayValue) {
          array_push($value, trim($arrayValue));
        }
      }
    }
    return $value;
  }

  /**
   * Merge two arrays, preserving entries in first one unless they are
   * overridden by ones in the second.
   * @param array1 First array.
   * @param array2 Second array.
   * @param override True/False whether values defined in array1 should be overriden by values defined in array2.
   * @return The merged array.
   */
  protected function configMerge($array1, $array2, $override)
  {
    $result = $array1;
    foreach(array_keys($array2) as $key)
    {
      if (!array_key_exists($key, $result)) {
        $result[$key] = $array2[$key];
      }
      else
      {
        foreach(array_keys($array2[$key]) as $subkey)
        {
          if ((array_key_exists($subkey, $result[$key]) && $override) || !array_key_exists($subkey, $result[$key])) {
            $result[$key][$subkey] = $array2[$key][$subkey];
          }
        }
      }
    }
    return $result;
  }
}
?>
