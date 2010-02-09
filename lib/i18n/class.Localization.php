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
require_once(BASE."wcmf/lib/util/class.ObjectFactory.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");


/**
 * @class Localization
 * @ingroup i18n
 * @brief Localization is used to store localized entity instances
 * and retrieve them back. Entity instances are localized value by value,
 * where a translation of a value of one instance into a specific language
 * is represented by one instance of the entity type that is defined
 * in the key 'translationType' in the configuration section 'i18n' (e.g. Translation).
 *
 * The translation entity type must have the attributes 'objectid',
 * 'attribute', 'translation', 'language' (all DATATYPE_ATTRIBUTE) with the
 * appropriate getter and setter methods.
 *
 * Localization is done against a default language, which is defined
 * in the configuration key 'defaultLanguage' in section 'i18n'. This means
 * that all entity data in the store is supposed to use the default language
 * except those data stored in Translation instances.
 *
 * All languages available for translation are either defined in the configuration
 * section 'languages', where each language has it's own entry: e.g. en = English
 * or in an entity tyoe that is defined in the key 'languageType' in the
 * configuration section 'i18n' (e.g. Language).  The entity type must have the
 * attributes 'code' and 'name' (all DATATYPE_ATTRIBUTE) with the appropriate
 * getter and setter methods.
 * If entity type and configuration section are defined, the configuration section is preferred.
 * Language key names may conform to ISO 639 language codes, but this is not mandatory.
 * One of the keys must be equal to the value of defaultLanguage.
 *
 * Generally only values whose datatype does not equal DATATYPE_IGNORE are
 * translatable.
 * To exclude values of a special type (like date values) from the translation,
 * they may be omitted in the array that is given in the key 'inputTypes' in
 * the configuration section 'i18n'. This array lists all input_types whose
 * translations are stored.
 *
 * @note: Localization is not aware of value datatypes. That means if an
 * entity has two values with the same name, but different datatype, localization
 * behaviour is not defined for these two values.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Localization
{
  private static $_instance = null;
  private $_supportedLanguages = null;

  private function __construct() {}

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!is_object(self::$_instance)) {
      self::$_instance = new Localization();
    }
    return self::$_instance;
  }

  /**
   * Get the default language that is used in the store.
   * Reads the key 'defaultLanguage' in the configuation section 'i18n'.
   * @return The default language value (e.g. en)
   */
  function getDefaultLanguage()
  {
    $parser = &InifileParser::getInstance();

    if (($defaultLanguage = $parser->getValue('defaultLanguage', 'i18n')) === false) {
      throw new ConfigurationException("No default language defined in configfile. ".$parser->getErrorMsg());
    }
    if (!array_key_exists($defaultLanguage, $this->getSupportedLanguages())) {
      throw new ConfigurationException("No supported language equals the default language '".$defaultLanguage."'");
    }
    return $defaultLanguage;
  }
  /**
   * Get all supported languages.
   * @return An associative array with the language codes as keys and the names as values.
   */
  function getSupportedLanguages()
  {
    if ($this->_supportedLanguages == null)
    {
      // check if the configuration section exists
      $parser = &InifileParser::getInstance();
      if (($languages = $parser->getSection('languages')) !== false) {
        $this->_supportedLanguages = $languages;
      }
      // if not, use the languageType
      else
      {
        $languageType = $parser->getValue('languageType', 'i18n');
        if ($languageType === false) {
          throw new ConfigurationException("No 'languageType' defined in configfile. ".$parser->getErrorMsg());
        }
        else
        {
          $persistenceFacade = &PersistenceFacade::getInstance();
          $languages = $persistenceFacade->loadObjects($languageType, BUILDEPTH_SINGLE);
          for($i=0; $i<sizeof($languages); $i++)
          {
            $curLanguage = &$languages[$i];
            $this->_supportedLanguages[$curLanguage->getCode()] = $curLanguage->getName();
          }
        }
      }
    }
    return $this->_supportedLanguages;
  }
  /**
   * Get the type name of the translation instances.
   * @return The type name.
   */
  function getTranslationType()
  {
    $parser = &InifileParser::getInstance();
    if (($type = $parser->getValue('translationType', 'i18n')) === false) {
      throw new ConfigurationException("No translation type defined in configfile. ".$parser->getErrorMsg());
    }
    return $type;
  }
  /**
   * Get the input types that are translatable.
   * @return The input type names.
   */
  function getIncludedInputTypes()
  {
    $parser = &InifileParser::getInstance();
    if (($inputTypes = $parser->getValue('inputTypes', 'i18n')) === false) {
      throw new ConfigurationException("No input types defined in configfile. ".$parser->getErrorMsg());
    }
    return $inputTypes;
  }
  /**
   * Get a newly created instance of the type defined in
   * the key 'type' in the configuration section 'i18n'.
   * @return An instance.
   */
  function &createTranslationInstance()
  {
    $objectFactory = &ObjectFactory::getInstance();
    $obj = &$objectFactory->createInstanceFromConfig('i18n', 'translationType');
    return $obj;
  }
  /**
   * Load a single translated object. The object is always loaded with BUILDDEPTH_SINGLE.
   * @param oid The id of the object to load the translation for.
   * @param lang The language of the translation to load.
   * @param useDefaults True/False wether to use the default language values
   *    for untranslated/empty values or not. Optional, default is true
   * @return A reference to the translated object.
   */
  function &loadTranslatedObject($oid, $lang, $useDefaults=true)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $object = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);

    $this->loadTranslation($object, $lang, $useDefaults, false);
    return $object;
  }
  /**
   * Load a translation of an entity for a specific language.
   * @param object A reference to the object to load the translation into. The object
   *    is supposed to have it's values in the default language.
   * @param lang The language of the translation to load.
   * @param useDefaults True/False wether to use the default language values
   *    for untranslated/empty values or not. Optional, default is true.
   * @param recursive True/False wether to load translations for children too or not.
   *    Optional, default is true. For recursive use, the object must have a getChildren method.
   */
  function loadTranslation($object, $lang, $useDefaults=true, $recursive=true)
  {
    if ($object == null) {
      throw new IllegalArgumentException("Cannot load translation for null");
    }
    // if the requested language is the default language, return the original object
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // load the translations and translate the object for any other language
    else
    {
      $type = $this->getTranslationType();
      $query = &PersistenceFacade::createObjectQuery($type);
      $tpl = &$query->getObjectTemplate($type);
      $tpl->setObjectid("= '".$object->getOID()."'");
      $tpl->setLanguage("= '".$lang."'");
      $translations = $query->execute(BUILDDEPTH_SINGLE);

      // set the translated values in the object
      $processor = new NodeProcessor('setTranslatedValue', array(&$translations, $useDefaults), new Localization());
      $processor->run($object, false);
    }

    // recurse if requested
    if ($recursive)
    {
      // translate children
      $children = $object->getChildren();
      for($i=0; $i<sizeOf($children); $i++) {
        $this->loadTranslation($children[$i], $lang, $useDefaults, $recursive);
      }
    }
  }
  /**
   * Save a translation of an entity for a specific language. Only the
   * values that have a non-empty value are considered as translations and stored.
   * Only values whose input_type property is listed in the 'inputTypes' key in
   * the configuration 'i18n' are stored.
   * @param object An instance of the entity type that holds the translations as values.
   * @param lang The language of the translation.
   * @param saveEmptyValues True/False wether to save empty translations or not.
   *    Optional, default is false
   * @param recursive True/False wether to save translations for children too or not.
   *    Optional, default is true. For recursive use, the object must have a getChildren method.
   */
  function saveTranslation(&$object, $lang, $saveEmptyValues=false, $recursive=true)
  {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // save the translations for any other language
    else
    {
      // get the existing translations for the requested language
      $type = $this->getTranslationType();
      $query = &PersistenceFacade::createObjectQuery($type);
      $tpl = &$query->getObjectTemplate($type);
      $tpl->setObjectid("= '".$object->getOID()."'");
      $tpl->setLanguage("= '".$lang."'");
      $translations = $query->execute(BUILDDEPTH_SINGLE);

      // save the translations
      $processor = new NodeProcessor('saveTranslatedValue', array(&$translations, $lang, $saveEmptyValues),
        new Localization());
      $processor->run($object, false);
    }

    // recurse if requested
    if ($recursive)
    {
      // translate children
      $children = $object->getChildren();
      for($i=0; $i<sizeOf($children); $i++) {
        $this->loadTranslation($children[$i], $lang, $useDefaults, $recursive);
      }
    }
  }
  /**
   * Remove translations for a given entity.
   * @param oid The id of the object
   * @param lang The language of the translation to remove. If null, all translations
   *    will be deleted [default: null]
   */
  function deleteTranslation($oid, $lang=null)
  {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // delete the translations for any other language
    else
    {
      // get the existing translations for the requested language or all languages
      $type = $this->getTranslationType();
      $query = &PersistenceFacade::createObjectQuery($type);
      $tpl = &$query->getObjectTemplate($type);
      $tpl->setObjectid("= '".$oid."'");
      if ($lang != null) {
        $tpl->setLanguage("= '".$lang."'");
      }
      $translationOIDs = $query->execute(false);

      // delete the found tranlations
      $persistenceFacade = &PersistenceFacade::getInstance();
      foreach ($translationOIDs as $curTranslationOID) {
        $persistenceFacade->delete($curTranslationOID);
      }
    }
  }
  /**
   * Delete all translations for a given language.
   * @param lang The language of the translations to remove
   */
  function deleteLanguage($lang)
  {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // delete the translations for any other language
    else
    {
      // get the existing translations for the requested language
      $type = $this->getTranslationType();
      $query = &PersistenceFacade::createObjectQuery($type);
      $tpl = &$query->getObjectTemplate($type);
      $tpl->setLanguage("= '".$lang."'");
      $translationOIDs = $query->execute(false);

      // delete the found tranlations
      $persistenceFacade = &PersistenceFacade::getInstance();
      foreach ($translationOIDs as $curTranslationOID) {
        $persistenceFacade->delete($curTranslationOID);
      }
    }
  }
  /**
   * Callback for setting translated values in the given object
   * @see NodeProcessor
   */
  function setTranslatedValue(&$obj, $valueName, $dataType, &$translations, $useDefaults)
  {
    $inputType = $obj->getValueProperty($valueName, 'input_type', $dataType);
    $inputTypes = $this->getIncludedInputTypes();
    if ($dataType != DATATYPE_IGNORE && in_array($inputType, $inputTypes))
    {
      // empty the value, if the default language values should not be used
      if (!$useDefaults) {
        $obj->setValue($valueName, null, $dataType);
      }
      // translate the value
      for ($i=0; $i<sizeof($translations); $i++)
      {
        $curValueName = $translations[$i]->getAttribute();
        if ($curValueName == $valueName)
        {
          $translation = $translations[$i]->getTranslation();
          if (!($useDefaults && strlen($translation) == 0)) {
            $obj->setValue($valueName, $translation, DATATYPE_ATTRIBUTE);
          }
          break;
        }
      }
    }
  }
  /**
   * Callback for saving translated values for the given object
   * @see NodeProcessor
   */
  function saveTranslatedValue(&$obj, $valueName, $dataType, &$existingTranslations, $lang, $saveEmptyValues)
  {
    $inputType = $obj->getValueProperty($valueName, 'input_type', $dataType);
    $inputTypes = $this->getIncludedInputTypes();
    if ($dataType != DATATYPE_IGNORE && in_array($inputType, $inputTypes))
    {
      $value = $obj->getValue($valueName, $dataType);
      if ($saveEmptyValues || strlen($value) > 0)
      {
        $translation = null;

        // check if a translation already exists
        for ($i=0; $i<sizeof($existingTranslations); $i++)
        {
          $curValueName = $existingTranslations[$i]->getAttribute();
          if ($curValueName == $valueName)
          {
            $translation = &$existingTranslations[$i];
            break;
          }
        }

        // if not, create a new translation
        if ($translation == null)
        {
          $persistenceFacade = &PersistenceFacade::getInstance();
          $translation = &$persistenceFacade->create($this->getTranslationType());
        }

        // set all required properties and save
        $translation->setObjectid($obj->getOID());
        $translation->setAttribute($valueName);
        $translation->setTranslation($obj->getValue($valueName, $dataType));
        $translation->setLanguage($lang);
        $translation->save();
      }
    }
  }
}
?>
