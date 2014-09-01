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
namespace wcmf\lib\config\impl;

use wcmf\lib\config\Configuration;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\WritableConfiguration;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\Log;
use wcmf\lib\io\FileUtil;
use wcmf\lib\io\IOException;
use wcmf\lib\util\StringUtil;

/**
 * InifileConfiguration reads the application configuraiton from ini files.
 * @note This class only supports ini files with sections.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InifileConfiguration implements Configuration, WritableConfiguration {

  private $_configArray = array(); // an assoziate array that holds sections with keys with values
  private $_comments = array(); // an assoziate array that holds the comments/blank lines in the file
                                // (each comment is attached to the following section/key)
                                // the key ';' holds the comments at the end of the file
  private $_lookupTable = array(); // an assoziate array that has lowercased section or section:key
                                   // keys and array(section, key) values for fast lookup

  private $_isModified = false;
  private $_addedFiles = array(); // files added to the configuration
  private $_containedFiles = array(); // all included files (also by config include)
  private $_useCache = true;

  private $_configPath = null;
  private $_configExtension = 'ini';

  /**
   * Constructor.
   * @param $configPath The path, either absolute or relative to the executed script
   */
  public function __construct($configPath) {
    $this->_configPath = $configPath;
  }

  /**
   * Get the filesystem path to the configuration files.
   * @return The path, either absolute or relative to the executed script
   */
  public function getConfigPath() {
    return $this->_configPath;
  }

  /**
   * Configuration interface
   */

  /**
   * @see Configuration::getConfigurations()
   */
  public function getConfigurations() {
    $fileUtil = new FileUtil();
    return $fileUtil->getFiles($this->_configPath, '/\.'.$this->_configExtension.'$/', true);
  }

  /**
   * @see Configuration::addConfiguration()
   * Name is the ini file to be parsed (relative to configPath)
   * @note ini files referenced in section 'config' key 'include' are parsed afterwards
   */
  public function addConfiguration($name, $processValues=true) {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Add configuration: ".$name, __CLASS__);
    }
    $filename = $this->_configPath.$name;

    // do nothing, if the requested file was the last parsed file
    // we don't only check if it's parsed, because order matters
    $numParsedFiles = sizeof($this->_addedFiles);
    $lastFile = $numParsedFiles > 0 ? $this->_addedFiles[$numParsedFiles-1] : '';
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Parsed files: ".$numParsedFiles.", last file: ".$lastFile, __CLASS__);
    }
    if ($numParsedFiles > 0 && $lastFile == $filename) {
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("Skipping", __CLASS__);
      }
      return;
    }

    if (file_exists($filename)) {
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("Adding...", __CLASS__);
      }
      // try to unserialize an already parsed ini file sequence
      $this->_addedFiles[] = $filename;
      if (!$this->unserialize($this->_addedFiles)) {
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Parse first time", __CLASS__);
        }
        $result = $this->processFile($filename, $this->_configArray, $this->_containedFiles);
        $this->_configArray = $result['config'];
        $this->_containedFiles = array_unique($result['files']);

        if ($processValues) {
          $this->processValues();
        }

        // re-build lookup table
        $this->buildLookupTable();

        // serialize the parsed ini file sequence
        $this->serialize();
      }
      else {
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Reuse from cache", __CLASS__);
        }
      }
    }
    else {
      throw new ConfigurationException('Configuration file '.$filename.' not found!');
    }
  }

  /**
   * Process the given file recursivly
   * @param $filename The filename
   * @param $configArray Configuration array
   * @param $parsedFiles Parsed files
   * @return Associative array with keys 'config' (configuration array) and 'files'
   * (array of parsed files)
   */
  protected function processFile($filename, $configArray=array(), $parsedFiles=array()) {
    // avoid circular includes
    if (!in_array($filename, $parsedFiles)) {
      $parsedFiles[] = $filename;

      $content = $this->_parse_ini_file($filename);

      // process includes
      $includes = $this->getConfigIncludes($content);
      if ($includes) {
        $this->processValue($includes);
        foreach ($includes as $include) {
          $result = $this->processFile($this->_configPath.$include, $configArray, $parsedFiles);
          $configArray = $this->configMerge($configArray, $result['config'], true);
          $parsedFiles = $result['files'];
        }
      }

      // process self
      $configArray = $this->configMerge($configArray, $content, true);
    }
    return array('config' => $configArray, 'files' => $parsedFiles);
  }

  /**
   * @see Configuration::getSections()
   */
  public function getSections() {
    return array_keys($this->_configArray);
  }

  /**
   * @see Configuration::hasSection()
   */
  public function hasSection($section) {
    return ($this->lookup($section) != null);
  }

  /**
   * @see Configuration::getSection()
   */
  public function getSection($section) {
    $lookupEntry = $this->lookup($section);
    if ($lookupEntry == null) {
      throw new ConfigurationException('Section \''.$section.'\' not found!');
    }
    else {
      return $this->_configArray[$lookupEntry[0]];
    }
  }

  /**
   * @see Configuration::hasValue()
   */
  public function hasValue($key, $section) {
    return ($this->lookup($section, $key) != null);
  }

  /**
   * @see Configuration::getValue()
   */
  public function getValue($key, $section) {
    $lookupEntry = $this->lookup($section, $key);
    if ($lookupEntry == null || sizeof($lookupEntry) == 1) {
      throw new ConfigurationException('Key \''.$key.'\' not found in section \''.$section.'\'!');
    }
    else {
      return $this->_configArray[$lookupEntry[0]][$lookupEntry[1]];
    }
  }

  /**
   * @see Configuration::getBooleanValue()
   */
  public function getBooleanValue($key, $section) {
    $value = $this->getValue($key, $section);
    return StringUtil::getBoolean($value);
  }

  /**
   * @see Configuration::getDirectoryValue()
   */
  public function getDirectoryValue($key, $section) {
    $value = $this->getValue($key, $section);
    $isArray = is_array($value);
    $values = !$isArray ? array($value) : $value;

    $result = array();
    foreach ($values as $path) {
      $absPath = WCMF_BASE.$path;
      $result[] = FileUtil::realpath($absPath).'/';
    }

    return $isArray ? $result : (sizeof($result) > 0 ? $result[0] : null);
  }

  /**
   * @see Configuration::getFileValue()
   */
  public function getFileValue($key, $section) {
    $value = $this->getValue($key, $section);
    $isArray = is_array($value);
    $values = !$isArray ? array($value) : $value;

    $result = array();
    foreach ($values as $path) {
      $absPath = WCMF_BASE.$path;
      $result[] = FileUtil::realpath(dirname($absPath)).'/'.basename($absPath);
    }

    return $isArray ? $result : (sizeof($result) > 0 ? $result[0] : null);
  }

  /**
   * @see Configuration::getKey()
   */
  public function getKey($value, $section) {
    $map = array_flip($this->getSection($section));
    if (!isset($map[$value])) {
      throw new ConfigurationException('Value \''.$value.'\' not found in section \''.$section.'\'!');
    }
    return $map[$value];
  }

  /**
   * WritableConfiguration interface
   */

  /**
   * @see WritableConfiguration::isEditable()
   */
  public function isEditable($section) {
    if ($this->hasValue('readonlySections', 'config')) {
      $readonlySections = $this->getValue('readonlySections', 'config');
      $sectionLower = strtolower($section);
      if (is_array($readonlySections)) {
        foreach($readonlySections as $readonlySection) {
          if ($sectionLower == strtolower($readonlySection)) {
            return false;
          }
        }
      }
    }
    return true;
  }

  /**
   * @see WritableConfiguration::isModified()
   */
  public function isModified() {
    return $this->_isModified;
  }

  /**
   * @see WritableConfiguration::createSection()
   */
  public function createSection($section) {
    $section = trim($section);
    if (strlen($section) == 0) {
      throw new IllegalArgumentException('Empty section names are not allowed!');
    }
    if ($this->hasSection($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' already exists!');
    }
    $this->_configArray[$section] = '';
    $this->buildLookupTable();
    $this->_isModified = true;
    return true;
  }

  /**
   * @see WritableConfiguration::removeSection()
   */
  public function removeSection($section) {
    if (!$this->isEditable($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' is not editable!');
    }
    $lookupEntry = $this->lookup($section);
    if ($lookupEntry != null) {
      unset($this->_configArray[$lookupEntry[0]]);
      $this->buildLookupTable();
      $this->_isModified = true;
    }
  }

  /**
   * @see WritableConfiguration::renameSection()
   */
  public function renameSection($oldname, $newname) {
    $newname = trim($newname);
    if (strlen($newname) == 0) {
      throw new IllegalArgumentException('Empty section names are not allowed!');
    }
    $lookupEntryOld = $this->lookup($oldname);
    if ($lookupEntryOld == null) {
      throw new IllegalArgumentException('Section \''.$oldname.'\' does not exist!');
    }
    if (!$this->isEditable($oldname)) {
      throw new IllegalArgumentException('Section \''.$oldname.'\' is not editable!');
    }
    $lookupEntryNew = $this->lookup($newname);
    if ($lookupEntryNew != null) {
      throw new IllegalArgumentException('Section \''.$newname.'\' already exists!');
    }
    // do rename
    $value = $this->_configArray[$lookupEntryOld[0]];
    $this->_configArray[$newname] = $value;
    unset($this->_configArray[$lookupEntryOld[0]]);
    $this->buildLookupTable();
    $this->_isModified = true;
  }

  /**
   * @see WritableConfiguration::setValue()
   */
  public function setValue($key, $value, $section, $createSection=true) {
    $key = trim($key);
    if (strlen($key) == 0) {
      throw new IllegalArgumentException('Empty key names are not allowed!');
    }
    $lookupEntrySection = $this->lookup($section);
    if ($lookupEntrySection == null && !$createSection) {
      throw new IllegalArgumentException('Section \''.$section.'\' does not exist!');
    }
    if ($lookupEntrySection != null && !$this->isEditable($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' is not editable!');
    }

    // create section if requested and determine section name
    if ($lookupEntrySection == null && $createSection) {
      $section = trim($section);
      $this->_configArray[$section] = array();
      $finalSectionName = $section;
    }
    else {
      $finalSectionName = $lookupEntrySection[0];
    }
    // determine key name
    if ($lookupEntrySection != null) {
      $lookupEntryKey = $this->lookup($section, $key);
      if ($lookupEntryKey == null) {
        // key does not exist yet
        $finalKeyName = $key;
      }
      else {
        $finalKeyName = $lookupEntryKey[1];
      }
    }
    else {
      $finalKeyName = $key;
    }
    $this->_configArray[$finalSectionName][$finalKeyName] = $value;
    $this->buildLookupTable();
    $this->_isModified = true;
  }

  /**
   * @see WritableConfiguration::removeKey()
   */
  public function removeKey($key, $section) {
    if (!$this->isEditable($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' is not editable!');
    }
    $lookupEntry = $this->lookup($section, $key);
    if ($lookupEntry != null) {
      unset($this->_configArray[$lookupEntry[0]][$lookupEntry[1]]);
      $this->buildLookupTable();
      $this->_isModified = true;
    }
  }

  /**
   * @see WritableConfiguration::renameKey()
   */
  public function renameKey($oldname, $newname, $section) {
    $newname = trim($newname);
    if (strlen($newname) == 0) {
      throw new IllegalArgumentException('Empty key names are not allowed!');
    }
    if (!$this->hasSection($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' does not exist!');
    }
    if (!$this->isEditable($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' is not editable!');
    }
    $lookupEntryOld = $this->lookup($section, $oldname);
    if ($lookupEntryOld == null) {
      throw new IllegalArgumentException('Key \''.$oldname.'\' does not exist in section \''.$section.'\'!');
    }
    $lookupEntryNew = $this->lookup($section, $newname);
    if ($lookupEntryNew != null) {
      throw new IllegalArgumentException('Key \''.$newname.'\' already exists in section \''.$section.'\'!');
    }
    // do rename
    $value = $this->_configArray[$lookupEntryOld[0]][$lookupEntryOld[1]];
    $this->_configArray[$lookupEntryOld[0]][$newname] = $value;
    unset($this->_configArray[$lookupEntryOld[0]][$lookupEntryOld[1]]);
    $this->buildLookupTable();
    $this->_isModified = true;
  }

  /**
   * @see WritableConfiguration::writeConfiguration()
   */
  public function writeConfiguration($name) {
    $filename = $name;
    $content = "";
    foreach($this->_configArray as $section => $values) {
      $sectionString = "[".$section."]";
      $content .= $this->_comments[$sectionString];
      $content .= $sectionString."\n";
      if (is_array($values)) {
        foreach($values as $key => $value) {
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

    if (!$fh = fopen($filename, 'w')) {
      throw new IOException('Can\'t open ini file \''.$filename.'\'!');
    }

    if (!fwrite($fh, $content)) {
      throw new IOException('Can\'t write ini file \''.$filename.'\'!');
    }
    fclose($fh);
    // clear the application cache, because it may become invalid
    $this->clearAllCache();
    $this->_isModified = false;
  }

  /**
   * Private interface
   */

  /**
   * Load in the ini file specified in filename, and return
   * the settings in a multidimensional array, with the section names and
   * settings included. All section names and keys are lowercased.
   * @param $filename The filename of the ini file to parse
   * @return An associative array containing the data
   *
   * @author: Sebastien Cevey <seb@cine7.net>
   *          Original Code base: <info@megaman.nl>
   *          Added comment handling/Removed process sections flag: Ingo Herwig
   */
  protected function _parse_ini_file($filename) {
    if (!file_exists($filename)) {
      throw new ConfigurationException('The config file '.$filename.' does not exist.');
    }
    $configArray = array();
    $sectionName = '';
    $lines = file($filename);
    $commentsPending = '';
    foreach($lines as $line) {
      $line = trim($line);
      // comments/blank lines
      if($line == '' || $line[0] == ';') {
        $commentsPending .= $line."\n";
        continue;
      }

      if($line[0] == '[' && $line[strlen($line)-1] == ']') {
        $sectionName = substr($line, 1, strlen($line)-2);
        $configArray[$sectionName] = array();

        // store comments/blank lines for section
        $this->_comments[$line] = $commentsPending;
        $commentsPending = '';
      }
      else {
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $configArray[$sectionName][$key] = $value;

        // store comments/blank lines for key
        $this->_comments[$sectionName][$key] = $commentsPending;
        $commentsPending = "";
      }
    }
    // store comments/blank lines from the end of the file
    $this->_comments[';'] = substr($commentsPending, 0, -1);

    return $configArray;
  }

  /**
   * Process the values in the ini array.
   * This method turns string values that hold array definitions
   * (comma separated values enclosed by curly brackets) into array values.
   */
  protected function processValues() {
    array_walk_recursive($this->_configArray, array($this, 'processValue'));
  }

  /**
   * Process the values in the ini array.
   * This method turns string values that hold array definitions
   * (comma separated values enclosed by curly brackets) into array values.
   * @param $value A reference to the value
   */
  protected function processValue(&$value) {
    if (!is_array($value)) {
      // decode encoded (%##) values
      if (preg_match ("/%/", $value)) {
        $value = urldecode($value);
      }
      // make arrays
      if(preg_match("/^{.*}$/", $value)) {
        $arrayValues = StringUtil::quotesplit(substr($value, 1, -1));
        $value = array();
        foreach ($arrayValues as $arrayValue) {
          $value[] = trim($arrayValue);
        }
      }
    }
  }

  /**
   * Merge two arrays, preserving entries in first one unless they are
   * overridden by ones in the second.
   * @param $array1 First array.
   * @param $array2 Second array.
   * @param $override Boolean whether values defined in array1 should be overriden by values defined in array2.
   * @return The merged array.
   */
  protected function configMerge($array1, $array2, $override) {
    $result = $array1;
    foreach(array_keys($array2) as $key) {
      if (!array_key_exists($key, $result)) {
        $result[$key] = $array2[$key];
      }
      else {
        foreach(array_keys($array2[$key]) as $subkey) {
          if ((array_key_exists($subkey, $result[$key]) && $override) || !isset($result[$key][$subkey]))
            $result[$key][$subkey] = $array2[$key][$subkey];
        }
      }
    }
    return $result;
  }

  /**
   * Search the given value for a 'include' key in a section named 'config' (case-insensivite)
   * @param $array The array to search in
   * @return Mixed
   */
  protected function getConfigIncludes($array) {
    $sectionMatches = null;
    if (preg_match('/(?:^|,)(config)(?:,|$)/i', join(',', array_keys($array)), $sectionMatches)) {
      $sectionKey = sizeof($sectionMatches) > 0 ? $sectionMatches[1] : null;
      if ($sectionKey) {
      $keyMatches = null;
        if (preg_match('/(?:^|,)(include)(?:,|$)/i', join(',', array_keys($array[$sectionKey])), $keyMatches)) {
          return sizeof($keyMatches) > 0 ? $array[$sectionKey][$keyMatches[1]] : null;
        }
      }
    }
    return null;
  }

  /**
   * Store the instance in the filesystem. If the instance is modified, this call is ignored.
   */
  protected function serialize() {
    if ($this->_useCache && !$this->isModified()) {
      $cacheFile = $this->getSerializeFilename($this->_addedFiles);
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("Serialize configuration: ".join(',', $this->_addedFiles)." to file: ".$cacheFile, __CLASS__);
      }
      if ($fh = @fopen($cacheFile, "w")) {
        if (@fwrite($fh, serialize(get_object_vars($this)))) {
          @fclose($fh);
        }
        // clear the application cache, because it may become invalid
        $this->clearAllCache();
      }
    }
  }

  /**
   * Retrieve parsed ini data from the filesystem and update the current instance.
   * If the current instance is modified or the last file given in parsedFiles
   * is newer than the serialized data, this call is ignored.
   * If InifileConfiguration class changed, the call will be ignored as well.
   * @param $parsedFiles An array of ini filenames that must be contained in the data.
   * @return Boolean whether the data could be retrieved or not
   */
  protected function unserialize($parsedFiles) {
    if ($this->_useCache && !$this->isModified()) {
      $cacheFile = $this->getSerializeFilename($parsedFiles);
      if (file_exists($cacheFile)) {
        $parsedFiles[] = __FILE__;
        if (!$this->checkFileDate($parsedFiles, $cacheFile)) {
          $vars = unserialize(file_get_contents($cacheFile));

          // check if included ini files were updated since last cache time
          $includes = $vars['_containedFiles'];
          if (is_array($includes)) {
            if ($this->checkFileDate($includes, $cacheFile)) {
              return false;
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
   * @param $parsedFiles An array of parsed filenames
   * @return Filename
   */
  protected function getSerializeFilename($parsedFiles) {
    $path = session_save_path();
    $filename = $path.'/wcmf_config_'.md5(realpath($this->_configPath).'/'.join('_', $parsedFiles));
    return $filename;
  }

  /**
   * Check if one file in fileList is newer than the referenceFile.
   * @param $fileList An array of files
   * @param $referenceFile The file to check against
   * @return True, if one of the files is newer, false else
   */
  protected function checkFileDate($fileList, $referenceFile) {
    foreach ($fileList as $file) {
      if (filemtime($file) > filemtime($referenceFile)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Clear application cache.
   */
  protected function clearAllCache() {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Clear all caches", __CLASS__);
    }
    if ($this->hasValue('cacheDir', 'application')) {
      $cacheDir = $this->getDirectoryValue('cacheDir', 'application');
      FileUtil::emptyDir($cacheDir);
    }
  }

  /**
   * Build the internal lookup table
   */
  protected function buildLookupTable() {
    $this->_lookupTable = array();
    foreach ($this->_configArray as $section => $entry) {
      // create section entry
      $lookupSectionKey = strtolower($section.':');
      $this->_lookupTable[$lookupSectionKey] = array($section);
      // create key entries
      foreach ($entry as $key => $value) {
        $lookupKey = strtolower($lookupSectionKey.$key);
        $this->_lookupTable[$lookupKey] = array($section, $key);
      }
    }
  }

  /**
   * Lookup section and key.
   * @param $section The section to lookup
   * @param $key The key to lookup (optional)
   * @return Array with section as first entry and key as second or null if not found
   */
  protected function lookup($section, $key=null) {
    $lookupKey = strtolower($section).':'.strtolower($key);
    if (isset($this->_lookupTable[$lookupKey])) {
      return $this->_lookupTable[$lookupKey];
    }
    return null;
  }
}
?>
