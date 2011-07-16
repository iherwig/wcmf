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
require_once(WCMF_BASE."wcmf/lib/util/StringUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/ArrayUtil.php");

/**
 * @class InifileParser
 * @ingroup Util
 * @brief InifileParser provides basic services for parsing a ini file from the file system.
 * @note This class only supports ini files with sections.
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
  private $_parsedFiles = array();
  private $_useCache = true;
  private function __construct() {}

  /**
   * InifileParser public readonly Interface
   */

  /**
   * Returns an instance of the class.
   * @return InifileParser instance
   */
  public static function getInstance()
  {
    if (!isset(self::$_instance)) {
      self::$_instance = new InifileParser();
    }
    return self::$_instance;
  }

  /**
   * Returns the errorMsg.
   * @return The error message.
   */
  public function getErrorMsg()
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
   * Parses an ini file and puts an array with all the key-values pairs into the object.
   * @param filename The filename of the ini file to parse
   * @param processValues True/False whether values should be processed after parsing (e.g. make arrays) [default: true]
   * @note ini files referenced in section 'config' key 'include' are parsed afterwards
   * @return True/False whether method succeeded.
   */
  public function parseIniFile($filename, $processValues=true)
  {
    // do nothing, if the requested file was the last parsed file
    $numParsedFiles = sizeof($this->_parsedFiles);
    if ($numParsedFiles > 0 && $this->_parsedFiles[sizeof($this->_parsedFiles)-1] == $filename) {
      return true;
    }

    global $CONFIG_PATH;
    if (file_exists($filename))
    {
      $this->_filename = $filename;

      // try to unserialize an already parsed ini file sequence
      $tmpSequence = $this->_parsedFiles;
      $tmpSequence[] = $filename;
      if (!$this->unserialize($tmpSequence))
      {
        // merge new with old values, overwrite redefined values
        $this->_iniArray = $this->configMerge($this->_iniArray, $this->_parse_ini_file($filename, true), true);

        // merge referenced ini files, don't override values
        if (($includes = $this->getValue('include', 'config')) !== false)
        {
          $this->processValue($includes);
          foreach($includes as $include) {
            $this->_iniArray = $this->configMerge($this->_iniArray, $this->_parse_ini_file($CONFIG_PATH.$include, true), false);
          }
        }
        if ($processValues) {
          $this->processValues();
        }
        // store the filename
        $this->_parsedFiles[] = $filename;

        // serialize the parsed ini file sequence
        $this->serialize();
      }
      return true;
    }
    else
    {
      $this->_errorMsg = "Configuration file ".$filename." not found!";
      return false;
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

    if (!isset($this->_iniArray[$section]))
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
    $sectionArray = $this->getSection($section, $caseSensitive);
    if ($sectionArray === false) {
      return false;
    }
    if (!$caseSensitive)
    {
      $matchingKeys = ArrayUtil::get_matching_values_i($key, array_keys($sectionArray));
      if (sizeof($matchingKeys) > 0) {
        $key = array_pop($matchingKeys);
      }
    }
    if (!array_key_exists($key, $sectionArray))
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
    if (($hiddenSections = $this->getValue('hiddenSections', 'config')) !== false)
    {
      $this->processValue($hiddenSections);
      if (is_array($hiddenSections) && in_array($section, $hiddenSections)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if a section is editable.
   * @param section The name of the section.
   * @note readyonly sections are defined as an array in section 'config' key 'readonlySections'
   */
  public function isEditable($section)
  {
    if (($readonlySections = $this->getValue('readonlySections', 'config')) !== false)
    {
      $this->processValue($readonlySections);
      if (is_array($readonlySections) && in_array($section, $readonlySections)) {
        return true;
      }
      else {
        return false;
      }
    }
    return true;
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
   * @return An associative array containing the data / false if any error occured
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
        $parts = explode("=", $line, 2);
        $property = trim($parts[0]);
        $value = trim($parts[1]);
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
    array_walk_recursive($this->_iniArray, array($this, 'processValue'));
  }

  /**
   * Process the values in the ini array.
   * This method turns string values that hold array definitions
   * (comma separated values enclosed by curly brackets) into array values.
   * @param value A reference to the value
   * @attention Internal use only.
   */
  protected function processValue(&$value)
  {
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
          if ((array_key_exists($subkey, $result[$key]) && $override) || !isset($result[$key][$subkey]))
            $result[$key][$subkey] = $array2[$key][$subkey];
        }
      }
    }
    return $result;
  }

  /**
   * Store the instance in the filesystem. If the instance is modified, this call is ignored.
   */
  protected function serialize()
  {
    if ($this->_useCache && !$this->isModified())
    {
      $cacheFile = $this->getSerializeFilename($this->_parsedFiles);
      if($fh = @fopen($cacheFile, "w"))
      {
        if(@fwrite($fh, serialize(get_object_vars($this)))) {
          @fclose($f);
        }
      }
    }
  }

  /**
   * Retrieve parsed ini data from the filesystem and update the current instance.
   * If the current instance is modified or the last file given in parsedFiles
   * is newer than the seriralized data, this call is ignored.
   * @param parsedFiles An array of ini filenames that must be contained in the data.
   * @param True/False wether the data could be retrieved or not
   */
  protected function unserialize($parsedFiles)
  {
    if ($this->_useCache && !$this->isModified())
    {
      $cacheFile = $this->getSerializeFilename($parsedFiles);
      if (file_exists($cacheFile))
      {
        if (!$this->checkFileDate($parsedFiles, $cacheFile))
        {
          $vars = unserialize(file_get_contents($cacheFile));

          // check if included ini files were updated since last cache time
          if (isset($vars['_iniArray']['config']))
          {
            global $CONFIG_PATH;
            $includes = $vars['_iniArray']['config']['include'];
            if (is_array($includes))
            {
              $includedFiles = array();
              foreach($includes as $include) {
                $includedFiles[] = $CONFIG_PATH.$include;
              }
              if ($this->checkFileDate($includedFiles, $cacheFile)) {
                return false;
              }
            }
          }

          // everything is up-to-date
          foreach($vars as $key=>$val) {
            eval("$"."this->$key = $"."vars['"."$key'];");
          }
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Get the filename for the serialized data that correspond to the the given ini file sequence.
   * @param parsedFiles An array of parsed filenames
   */
  protected function getSerializeFilename($parsedFiles)
  {
    global $CONFIG_PATH;
    $path = session_save_path();
    $filename = $path.'/'.urlencode(realpath($CONFIG_PATH)."/".join('_', $parsedFiles));
    return $filename;
  }

  /**
   * Check if one file in fileList is newer than the referenceFile.
   * @param fileList An array of files
   * @param referenceFile The file to check against
   * @return True, if one of the files is newer, false else
   */
  protected function checkFileDate($fileList, $referenceFile)
  {
    foreach ($fileList as $file) {
      if (filemtime($file) > filemtime($referenceFile)) {
        return true;
      }
    }
    return false;
  }
}
?>
