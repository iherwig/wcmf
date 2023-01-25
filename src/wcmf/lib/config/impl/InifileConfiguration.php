<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\config\impl;

use wcmf\lib\config\ConfigChangeEvent;
use wcmf\lib\config\Configuration;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\WritableConfiguration;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\LogTrait;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\io\IOException;
use wcmf\lib\util\StringUtil;

/**
 * InifileConfiguration reads the application configuration from ini files.
 * @note This class only supports ini files with sections.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InifileConfiguration implements Configuration, WritableConfiguration {
  use LogTrait;

  /** @var array<string, mixed> assoziate array that holds sections with keys with values */
  private array $configArray = [];

  /** @var array<string, string> assoziate array that holds the comments/blank lines in the file
   * (each comment is attached to the following section/key)
   * the key ';' holds the comments at the end of the file
   */
  private array $comments = [];

  /** @var array<string, string> assoziate array that has lowercased section or section:key
   * keys and [section, key] values for fast lookup
   */
  private array $lookupTable = [];

  /** @var array<string> files added to the configuration */
  private array $addedFiles = [];

  /** @var array<string> all included files (also by config include) */
  private array $containedFiles = [];

  private bool $isModified = false;

  private string $configPath;
  private string $configExtension = 'ini';
  private ?string $cachePath = null;

  private FileUtil $fileUtil;

  /**
   * Constructor.
   * @param string $configPath The path, either absolute or relative to the executed script
   * @param string $cachePath The cache path, either absolute or relative to the executed script (optional)
   */
  public function __construct(string $configPath, string $cachePath=null) {
    $this->configPath = $configPath;
    $this->cachePath = $cachePath;
    $this->fileUtil = new FileUtil();
  }

  public function __toString(): string {
    $configArray = $this->configArray;
    ksort($configArray);
    $result = '';
    foreach($configArray as $section => $config) {
      $result .= PHP_EOL.'['.$section.']'.PHP_EOL;
      ksort($config);
      foreach ($config as $key => $value) {
        $result .= $key.' = '.json_encode($value).PHP_EOL;
      }
    }
    return $result;
  }

  /**
   * Get the file system path to the configuration files.
   * @return string The path, either absolute or relative to the executed script
   */
  public function getConfigPath(): string {
    return $this->configPath;
  }

  /**
   * Configuration interface
   */

  /**
   * @see Configuration::getConfigurations()
   */
  public function getConfigurations(): array {
    return $this->fileUtil->getFiles($this->configPath, '/\.'.$this->configExtension.'$/', true);
  }

  /**
   * @see Configuration::addConfiguration()
   * Name is the ini file to be parsed (relative to configPath)
   * @note ini files referenced in section 'config' key 'include' are parsed afterwards
   */
  public function addConfiguration(string $name, bool $processValues=true): void {
    $logger = self::logger();
    if ($logger->isDebugEnabled()) {
      $logger->debug("Add configuration: ".$name);
    }
    $filename = $this->configPath.$name;

    // do nothing, if the requested file was the last parsed file
    // we don't only check if it's parsed, because order matters
    $numParsedFiles = sizeof($this->addedFiles);
    $lastFile = $numParsedFiles > 0 ? $this->addedFiles[$numParsedFiles-1] : '';
    if ($logger->isDebugEnabled()) {
      $logger->debug("Parsed files: ".$numParsedFiles.", last file: ".$lastFile);
      foreach($this->addedFiles as $addedFile) {
        $logger->debug("File date ".$addedFile.": ".@filemtime($addedFile));
      }
      $cachedFile = $this->getSerializeFilename($this->addedFiles);
      $logger->debug("Cache file date ".$cachedFile.": ".@filemtime($cachedFile));
    }
    if ($numParsedFiles > 0 && $lastFile == $filename &&
            !$this->checkFileDate($this->addedFiles, $this->getSerializeFilename($this->addedFiles))) {
      if ($logger->isDebugEnabled()) {
        $logger->debug("Skipping");
      }
      return;
    }

    if (!file_exists($filename)) {
      throw new ConfigurationException('Configuration file '.$filename.' not found!');
    }

    if ($logger->isDebugEnabled()) {
      $logger->debug("Adding...");
    }
    // try to unserialize an already parsed ini file sequence
    $this->addedFiles[] = $filename;
    if (!$this->unserialize($this->addedFiles)) {
      if ($logger->isDebugEnabled()) {
        $logger->debug("Parse first time");
      }
      $result = $this->processFile($filename, $this->configArray, $this->containedFiles);
      $this->configArray = $result['config'];
      $this->containedFiles = array_unique($result['files']);

      if ($processValues) {
        $this->processValues();
      }

      // re-build lookup table
      $this->buildLookupTable();

      // serialize the parsed ini file sequence
      $this->serialize();

      // notify configuration change listeners
      $this->configChanged();
    }
    else {
      if ($logger->isDebugEnabled()) {
        $logger->debug("Reuse from cache");
      }
    }
  }

  /**
   * Process the given file recursively
   * @param string $filename The filename
   * @param array<string, mixed> $configArray Configuration array
   * @param array<string> $parsedFiles Parsed files
   * @return array{'config': array, 'files': array} with configuration array and parsed files
   */
  protected function processFile(string $filename, array $configArray=[], array $parsedFiles=[]) {
    // avoid circular includes
    if (!in_array($filename, $parsedFiles)) {
      $parsedFiles[] = $filename;

      $content = $this->parseIniFile($filename);

      // process includes
      $includes = $this->getConfigIncludes($content);
      if ($includes) {
        $this->processValue($includes);
        foreach ($includes as $include) {
          $result = $this->processFile($this->configPath.$include, $configArray, $parsedFiles);
          $configArray = $this->configMerge($configArray, $result['config'], true);
          $parsedFiles = $result['files'];
        }
      }

      // process self
      $configArray = $this->configMerge($configArray, $content, true);
    }
    return ['config' => $configArray, 'files' => $parsedFiles];
  }

  /**
   * @see Configuration::getSections()
   */
  public function getSections(): array {
    return array_keys($this->configArray);
  }

  /**
   * @see Configuration::hasSection()
   */
  public function hasSection(string $section): bool {
    return ($this->lookup($section) != null);
  }

  /**
   * @see Configuration::getSection()
   */
  public function getSection(string $section, bool $includeMeta=false): array {
    $lookupEntry = $this->lookup($section);
    if ($lookupEntry == null) {
      throw new ConfigurationException('Section \''.$section.'\' not found!');
    }
    else {
      if ($includeMeta) {
        return $this->configArray[$lookupEntry[0]];
      }
      else {
        return array_filter($this->configArray[$lookupEntry[0]], function($k) {
          return !preg_match('/^__/', $k);
        }, \ARRAY_FILTER_USE_KEY);
      }
    }
  }

  /**
   * @see Configuration::hasValue()
   */
  public function hasValue(string $key, string $section): bool {
    return ($this->lookup($section, $key) != null);
  }

  /**
   * @see Configuration::getValue()
   */
  public function getValue(string $key, string $section) {
    $lookupEntry = $this->lookup($section, $key);
    if ($lookupEntry == null || sizeof($lookupEntry) == 1) {
      throw new ConfigurationException('Key \''.$key.'\' not found in section \''.$section.'\'!');
    }
    else {
      return $this->configArray[$lookupEntry[0]][$lookupEntry[1]];
    }
  }

  /**
   * @see Configuration::getBooleanValue()
   */
  public function getBooleanValue(string $key, string $section) {
    $value = $this->getValue($key, $section);
    return StringUtil::getBoolean($value);
  }

  /**
   * @see Configuration::getDirectoryValue()
   */
  public function getDirectoryValue(string $key, string $section) {
    $value = $this->getValue($key, $section);
    $isArray = is_array($value);
    $values = !$isArray ? [$value] : $value;

    $result = [];
    foreach ($values as $path) {
      $absPath = WCMF_BASE.$path;
      $result[] = $this->fileUtil->realpath($absPath).'/';
    }

    return $isArray ? $result : (sizeof($result) > 0 ? $result[0] : null);
  }

  /**
   * @see Configuration::getFileValue()
   */
  public function getFileValue(string $key, string $section) {
    $value = $this->getValue($key, $section);
    $isArray = is_array($value);
    $values = !$isArray ? [$value] : $value;

    $result = [];
    foreach ($values as $path) {
      $absPath = WCMF_BASE.$path;
      $result[] = $this->fileUtil->realpath(dirname($absPath)).'/'.$this->fileUtil->basename($absPath);
    }

    return $isArray ? $result : (sizeof($result) > 0 ? $result[0] : null);
  }

  /**
   * @see Configuration::getKey()
   */
  public function getKey(string $value, string $section): string {
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
  public function isEditable(string $section): bool {
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
  public function isModified(): bool {
    return $this->isModified;
  }

  /**
   * @see WritableConfiguration::createSection()
   */
  public function createSection(string $section): void {
    $section = trim($section);
    if (strlen($section) == 0) {
      throw new IllegalArgumentException('Empty section names are not allowed!');
    }
    if ($this->hasSection($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' already exists!');
    }
    $this->configArray[$section] = '';
    $this->buildLookupTable();
    $this->isModified = true;
  }

  /**
   * @see WritableConfiguration::removeSection()
   */
  public function removeSection(string $section): void {
    if (!$this->isEditable($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' is not editable!');
    }
    $lookupEntry = $this->lookup($section);
    if ($lookupEntry != null) {
      unset($this->configArray[$lookupEntry[0]]);
      $this->buildLookupTable();
      $this->isModified = true;
    }
  }

  /**
   * @see WritableConfiguration::renameSection()
   */
  public function renameSection(string $oldname, string $newname): void {
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
    $value = $this->configArray[$lookupEntryOld[0]];
    $this->configArray[$newname] = $value;
    unset($this->configArray[$lookupEntryOld[0]]);
    $this->buildLookupTable();
    $this->isModified = true;
  }

  /**
   * @see WritableConfiguration::setValue()
   */
  public function setValue(string $key, $value, string $section, bool $createSection=true): void {
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
      $this->configArray[$section] = [];
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
    $this->configArray[$finalSectionName][$finalKeyName] = $value;
    $this->buildLookupTable();
    $this->isModified = true;
  }

  /**
   * @see WritableConfiguration::removeKey()
   */
  public function removeKey(string $key, string $section): void {
    if (!$this->isEditable($section)) {
      throw new IllegalArgumentException('Section \''.$section.'\' is not editable!');
    }
    $lookupEntry = $this->lookup($section, $key);
    if ($lookupEntry != null) {
      unset($this->configArray[$lookupEntry[0]][$lookupEntry[1]]);
      $this->buildLookupTable();
      $this->isModified = true;
    }
  }

  /**
   * @see WritableConfiguration::renameKey()
   */
  public function renameKey(string $oldname, string $newname, string $section): void {
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
    $value = $this->configArray[$lookupEntryOld[0]][$lookupEntryOld[1]];
    $this->configArray[$lookupEntryOld[0]][$newname] = $value;
    unset($this->configArray[$lookupEntryOld[0]][$lookupEntryOld[1]]);
    $this->buildLookupTable();
    $this->isModified = true;
  }

  /**
   * @see WritableConfiguration::writeConfiguration()
   */
  public function writeConfiguration(string $name): void {
    $filename = $name;
    $content = "";
    foreach($this->configArray as $section => $values) {
      $sectionString = "[".$section."]";
      $content .= $this->comments[$sectionString];
      $content .= $sectionString."\n";
      if (is_array($values)) {
        foreach($values as $key => $value) {
          if (is_array($value)) {
            $value = "{".join(", ", $value)."}";
          }
          // unescape double quotes
          $value = str_replace("\\\"", "\"", $value);
          $content .= $this->comments[$section][$key];
          $content .= $key." = ".$value."\n";
        }
      }
    }
    $content .= $this->comments[';'];

    if (!$fh = fopen($filename, 'w')) {
      throw new IOException('Can\'t open ini file \''.$filename.'\'!');
    }

    if (!fwrite($fh, $content)) {
      throw new IOException('Can\'t write ini file \''.$filename.'\'!');
    }
    fclose($fh);

    // notify configuration change listeners
    $this->configChanged();
    $this->isModified = false;
  }

  /**
   * Private interface
   */

  /**
   * Load in the ini file specified in filename, and return
   * the settings in a multidimensional array, with the section names and
   * settings included. All section names and keys are lowercased.
   * @param string $filename The filename of the ini file to parse
   * @return array containing the data
   *
   * @author: Sebastien Cevey <seb@cine7.net>
   *          Original Code base: <info@megaman.nl>
   *          Added comment handling/Removed process sections flag: Ingo Herwig
   */
  protected function parseIniFile(string $filename): array {
    if (!file_exists($filename)) {
      throw new ConfigurationException('The config file '.$filename.' does not exist.');
    }
    $configArray = [];
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
        $configArray[$sectionName] = [];

        // store comments/blank lines for section
        $this->comments[$line] = $commentsPending;
        $commentsPending = '';
      }
      else {
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $configArray[$sectionName][$key] = $value;

        // store comments/blank lines for key
        $this->comments[$sectionName][$key] = $commentsPending;
        $commentsPending = "";
      }
    }
    // store comments/blank lines from the end of the file
    $this->comments[';'] = substr($commentsPending, 0, -1);

    return $configArray;
  }

  /**
   * Process the values in the ini array.
   * This method turns string values that hold array definitions
   * (comma separated values enclosed by curly brackets) into array values.
   */
  protected function processValues(): void {
    array_walk_recursive($this->configArray, [$this, 'processValue']);
  }

  /**
   * Process the values in the ini array.
   * This method turns string values that hold array definitions
   * (comma separated values enclosed by curly brackets) into array values.
   * @param mixed $value A reference to the value
   */
  protected function processValue(&$value): void {
    if (!is_array($value)) {
      // decode encoded (%##) values
      if (preg_match ("/%/", $value)) {
        $value = urldecode($value);
      }
      // make arrays
      if(preg_match("/^{.*}$/", $value)) {
        $arrayValues = StringUtil::quotesplit(substr($value, 1, -1));
        $value = [];
        foreach ($arrayValues as $arrayValue) {
          $value[] = trim($arrayValue);
        }
      }
    }
  }

  /**
   * Merge the second array into the first, preserving entries of the first array
   * unless the second array contains the special key '__inherit' set to false
   * or they are re-defined in the second array.
   * @param array $array1 First array.
   * @param array $array2 Second array.
   * @param bool $override Boolean whether values defined in array1 should be overridden by values defined in array2.
   * @return array
   */
  protected function configMerge(array $array1, array $array2, bool $override) {
    $result = $array1;
    foreach(array_keys($array2) as $key) {
      if (!array_key_exists($key, $result)) {
        // copy complete section, if new
        $result[$key] = $array2[$key];
      }
      else {
        // process existing section
        // remove old keys, if inheritence is disabled
        $inherit = !isset($array2[$key]['__inherit']) || $array2[$key]['__inherit'] == false;
        if (!$inherit) {
          foreach(array_keys($result[$key]) as $subkey) {
            unset($result[$key][$subkey]);
          }
        }
        // merge in new keys
        foreach(array_keys($array2[$key]) as $subkey) {
          if ((array_key_exists($subkey, $result[$key]) && $override) || !isset($result[$key][$subkey])) {
            $result[$key][$subkey] = $array2[$key][$subkey];
          }
        }
      }
    }
    return $result;
  }

  /**
   * Search the given value for a 'include' key in a section named 'config' (case-insensivite)
   * @param array $array The array to search in
   * @return mixed
   */
  protected function getConfigIncludes(array $array) {
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
   * Store the instance in the file system. If the instance is modified, this call is ignored.
   */
  protected function serialize(): void {
    if (!$this->isModified() && ($cacheFile = $this->getSerializeFilename($this->addedFiles))) {
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug("Serialize configuration: ".join(',', $this->addedFiles)." to file: ".$cacheFile);
      }
      $this->fileUtil->mkdirRec(dirname($cacheFile));
      if ($fh = @fopen($cacheFile, "w")) {
        if (@fwrite($fh, serialize(array_filter(get_object_vars($this), function($value, $name) {
          return $name != 'comments'; // don't store comments
        }, ARRAY_FILTER_USE_BOTH)))) {
          @fclose($fh);
        }
      }
    }
  }

  /**
   * Retrieve parsed ini data from the file system and update the current instance.
   * If the current instance is modified or any file given in parsedFiles
   * is newer than the serialized data, this call is ignored.
   * If InifileConfiguration class changed, the call will be ignored as well.
   * @param array<string> $parsedFiles An array of ini filenames that must be contained in the data.
   * @return bool whether the data could be retrieved or not
   */
  protected function unserialize(array $parsedFiles): bool {
    if (!$this->isModified() && ($cacheFile = $this->getSerializeFilename($parsedFiles)) && file_exists($cacheFile)) {
      $parsedFiles[] = __FILE__;
      if (!$this->checkFileDate($parsedFiles, $cacheFile)) {
        $vars = unserialize(file_get_contents($cacheFile));

        // check if included ini files were updated since last cache time
        $includes = $vars['containedFiles'];
        if (is_array($includes)) {
          if ($this->checkFileDate($includes, $cacheFile)) {
            return false;
          }
        }

        // everything is up-to-date
        foreach($vars as $key => $val) {
          $this->$key = $val;
        }
        return true;
      }
    }
    return false;
  }

  /**
   * Get the filename for the serialized data that correspond to the the given ini file sequence.
   * NOTE: The method returns null, if no cache path is configured
   * @param array<string> $parsedFiles An array of parsed filenames
   * @return string
   */
  protected function getSerializeFilename(array $parsedFiles): string {
    if (!$this->cachePath) {
      return null;
    }
    $path = $this->fileUtil->realpath($this->cachePath).'/';
    $filename = $path.'wcmf_config_'.hash('sha256', $this->fileUtil->realpath($this->configPath).'/'.join('_', $parsedFiles));
    return $filename;
  }

  /**
   * Check if one file in fileList is newer than the referenceFile.
   * @param array<string> $fileList An array of files
   * @param string $referenceFile The file to check against
   * @return bool, true if one of the files is newer, false else
   */
  protected function checkFileDate(array $fileList, string $referenceFile): bool {
    foreach ($fileList as $file) {
      if (@filemtime($file) > @filemtime($referenceFile)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Notify configuration change listeners
   */
  protected function configChanged(): void {
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Configuration is changed");
    }
    if (ObjectFactory::isConfigured()) {
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug("Emitting change event");
      }
      ObjectFactory::getInstance('eventManager')->dispatch(ConfigChangeEvent::NAME,
              new ConfigChangeEvent());
    }
  }

  /**
   * Build the internal lookup table
   */
  protected function buildLookupTable(): void {
    $this->lookupTable = [];
    foreach ($this->configArray as $section => $entry) {
      // create section entry
      $lookupSectionKey = strtolower($section.':');
      $this->lookupTable[$lookupSectionKey] = [$section];
      // create key entries
      foreach ($entry as $key => $value) {
        $lookupKey = strtolower($lookupSectionKey.$key);
        $this->lookupTable[$lookupKey] = [$section, $key];
      }
    }
  }

  /**
   * Lookup section and key.
   * @param string $section The section to lookup
   * @param string $key The key to lookup (optional)
   * @return array with section as first entry and key as second or null if not found
   */
  protected function lookup(string $section, string $key=null): ?array {
    $lookupKey = strtolower($section).':'.strtolower($key);
    if (isset($this->lookupTable[$lookupKey])) {
      return $this->lookupTable[$lookupKey];
    }
    return null;
  }
}
?>
