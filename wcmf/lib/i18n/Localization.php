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
namespace wcmf\lib\i18n;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;

/**
 * Localization is used to store localized entity instances
 * and retrieve them back. Entity instances are localized value by value,
 * where a translation of a value of one instance into a specific language
 * is represented by one instance of the entity type that is defined
 * in the key 'translationType' in the configuration section 'i18n' (e.g. Translation).
 *
 * The translation entity type must have the attributes 'objectid',
 * 'attribute', 'translation', 'language' with the
 * appropriate getter and setter methods.
 *
 * Localization is done against a default language, which is defined
 * in the configuration key 'defaultLanguage' in section 'i18n'. This means
 * that all entity data in the store is supposed to use the default language
 * except those data stored in Translation instances.
 *
 * All languages available for translation are either defined in the configuration
 * section 'languages', where each language has it's own entry: e.g. en = English
 * or in an entity type that is defined in the key 'languageType' in the
 * configuration section 'i18n' (e.g. Language).  The entity type must have the
 * attributes 'code' and 'name' with the appropriate getter and setter methods.
 * If entity type and configuration section are defined, the configuration section is preferred.
 * Language key names may conform to ISO 639 language codes, but this is not mandatory.
 * One of the keys must be equal to the value of defaultLanguage.
 *
 * Generally only values are translatable.
 * To exclude values of a special type (like date values) from the translation,
 * they may be omitted in the array that is given in the key 'inputTypes' in
 * the configuration section 'i18n'. This array lists all input_types whose
 * translations are stored.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Localization {

  private static $_instance = null;
  private $_supportedLanguages = null;

  private function __construct() {}

  /**
   * Returns the only instance of the class.
   * @return Localization instance
   */
  public static function getInstance() {
    if (!isset(self::$_instance)) {
      self::$_instance = new Localization();
    }
    return self::$_instance;
  }

  /**
   * Get the default language that is used in the store.
   * Reads the key 'defaultLanguage' in the configuation section 'i18n'.
   * @return The default language value (e.g. en)
   */
  public function getDefaultLanguage() {
    $configuration = ObjectFactory::getInstance('configuration');

    if (($defaultLanguage = $configuration->getValue('defaultLanguage', 'i18n')) === false) {
      throw new ConfigurationException("No default language defined in configfile. ".$configuration->getErrorMsg());
    }
    $supportedLanguages = $this->getSupportedLanguages();
    if (!isset($supportedLanguages[$defaultLanguage])) {
      throw new ConfigurationException("No supported language equals the default language '".$defaultLanguage."'");
    }
    return $defaultLanguage;
  }

  /**
   * Get all supported languages.
   * @return An associative array with the language codes as keys and the names as values.
   */
  public function getSupportedLanguages() {
    if ($this->_supportedLanguages == null) {
      // check if the configuration section exists
      $configuration = ObjectFactory::getInstance('configuration');
      if (($languages = $configuration->getSection('languages')) !== false) {
        $this->_supportedLanguages = $languages;
      }
      // if not, use the languageType
      else {
        $languageType = $configuration->getValue('languageType', 'i18n');
        if ($languageType === false) {
          throw new ConfigurationException("No 'languageType' defined in configfile. ".$configuration->getErrorMsg());
        }
        else {
          $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
          $languages = $persistenceFacade->loadObjects($languageType, BUILDEPTH_SINGLE);
          for($i=0; $i<sizeof($languages); $i++) {
            $curLanguage = $languages[$i];
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
  public static function getTranslationType() {
    $configuration = ObjectFactory::getInstance('configuration');
    if (($type = $configuration->getValue('translationType', 'i18n')) === false) {
      throw new ConfigurationException("No translation type defined in configfile. ".$configuration->getErrorMsg());
    }
    return $type;
  }

  /**
   * Get the input types that are translatable.
   * @return The input type names.
   */
  protected static function getIncludedInputTypes() {
    $configuration = ObjectFactory::getInstance('configuration');
    if (($inputTypes = $configuration->getValue('inputTypes', 'i18n')) === false) {
      throw new ConfigurationException("No input types defined in configfile. ".$configuration->getErrorMsg());
    }
    return $inputTypes;
  }

  /**
   * Get a newly created instance of the type defined in
   * the key 'type' in the configuration section 'i18n'.
   * @return An instance.
   */
  public static function createTranslationInstance() {
    $obj = ObjectFactory::getInstance('persistenceFacade')->create(
            self::getTranslationType(), BuildDepth::SINGLE);
    return $obj;
  }

  /**
   * Load a single translated object. The object is always loaded with BuildDepth::SINGLE.
   * @param oid The object id of the object to load the translation for.
   * @param lang The language of the translation to load.
   * @param useDefaults True/False wether to use the default language values
   *    for untranslated/empty values or not. Optional, default is true
   * @return A reference to the translated object.
   */
  public function loadTranslatedObject(ObjectId $oid, $lang, $useDefaults=true) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $object = $persistenceFacade->load($oid, BuildDepth::SINGLE);

    $this->loadTranslation($object, $lang, $useDefaults, false);
    return $object;
  }

  /**
   * Load a translation of an entity for a specific language.
   * @note The object state will be changed to dirty by this operation, so make
   * sure that the object is not attached to the transaction.
   * @param object A reference to the object to load the translation into. The object
   *    is supposed to have it's values in the default language.
   * @param lang The language of the translation to load.
   * @param useDefaults True/False wether to use the default language values
   *    for untranslated/empty values or not. Optional, default is true.
   * @param recursive True/False wether to load translations for children too or not.
   *    Optional, default is true. For recursive use, the object must have a getChildren method.
   */
  public function loadTranslation(PersistentObject $object, $lang, $useDefaults=true, $recursive=true) {
    if ($object == null) {
      throw new IllegalArgumentException("Cannot load translation for null");
    }
    // if the requested language is the default language, return the original object
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // load the translations and translate the object for any other language
    else {
      $type = self::getTranslationType();
      $query = new ObjectQuery($type);
      $tpl = $query->getObjectTemplate($type);
      $tpl->setObjectid(Criteria::asValue("=", $object->getOID()->__toString()));
      $tpl->setLanguage(Criteria::asValue("=", $lang));
      $translations = $query->execute(BuildDepth::SINGLE);

      // set the translated values in the object
      $iter = new NodeValueIterator($object, false);
      for($iter->rewind(); $iter->valid(); $iter->next()) {
        $curIterNode = $iter->currentNode();
        $this->setTranslatedValue($curIterNode, $iter->key(), $translations, $useDefaults);
      }
    }

    // recurse if requested
    if ($recursive) {
      // translate children
      $children = $object->getChildren();
      for($i=0, $count=sizeOf($children); $i<$count; $i++) {
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
  public function saveTranslation(PersistentObject $object, $lang, $saveEmptyValues=false, $recursive=true) {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // save the translations for any other language
    else {
      $object->beforeUpdate();

      // get the existing translations for the requested language
      $type = self::getTranslationType();
      $query = new ObjectQuery($type);
      $tpl = $query->getObjectTemplate($type);
      $tpl->setObjectid(Criteria::asValue("=", $object->getOID()->__toString()));
      $tpl->setLanguage(Criteria::asValue("=", $lang));
      $translations = $query->execute(BuildDepth::SINGLE);

      // save the translations, ignore pk values
      $pkNames = $object->getPkNames();
      $iter = new NodeValueIterator($object, false);
      for($iter->rewind(); $iter->valid(); $iter->next()) {
        $valueName = $iter->key();
        if (!in_array($valueName, $pkNames)) {
          $curIterNode = $iter->currentNode();
          $this->saveTranslatedValue($curIterNode, $valueName, $translations, $lang, $saveEmptyValues);
        }
      }
    }

    // recurse if requested
    if ($recursive)
    {
      // translate children
      $children = $object->getChildren();
      for($i=0; $i<sizeOf($children); $i++) {
        $this->saveTranslation($children[$i], $lang, $saveEmptyValues, $recursive);
      }
    }
  }

  /**
   * Remove translations for a given entity.
   * @param oid The id of the object
   * @param lang The language of the translation to remove. If null, all translations
   *    will be deleted [default: null]
   */
  public function deleteTranslation(ObjectId $oid, $lang=null) {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // delete the translations for any other language
    else {
      // get the existing translations for the requested language or all languages
      $type = self::getTranslationType();
      $query = new ObjectQuery($type);
      $tpl = $query->getObjectTemplate($type);
      $tpl->setObjectid(Criteria::asValue("=", $oid->__toString()));
      if ($lang != null) {
        $tpl->setLanguage(Criteria::asValue("=", $lang));
      }
      $translations = $query->execute(BuildDepth::SINGLE);

      // delete the found tranlations
      foreach ($translations as $curTranslation) {
        $curTranslation->delete();
      }
    }
  }

  /**
   * Delete all translations for a given language.
   * @param lang The language of the translations to remove
   */
  public function deleteLanguage($lang) {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // delete the translations for any other language
    else {
      // get the existing translations for the requested language
      $type = self::getTranslationType();
      $query = new ObjectQuery($type);
      $tpl = $query->getObjectTemplate($type);
      $tpl->setLanguage(Criteria::asValue("=", $lang));
      $translations = $query->execute(BuildDepth::SINGLE);

      // delete the found tranlations
      foreach ($translations as $curTranslation) {
        $curTranslation->delete();
      }
    }
  }

  /**
   * Set a translated value in the given PersistentObject instance.
   * @param object The object to set the value on. The object
   *    is supposed to have it's values in the default language.
   * @param valueName The name of the value to translate
   * @param translations An array of translation instances for the object.
   * @param useDefaults True/False wether to use the default language if no translation is
   *    found or not.
   */
  private function setTranslatedValue(PersistentObject $object, $valueName, array $translations, $useDefaults) {
    $inputType = $object->getValueProperty($valueName, 'input_type');
    $inputTypes = self::getIncludedInputTypes();
    if (in_array($inputType, $inputTypes)) {
      // empty the value, if the default language values should not be used
      if (!$useDefaults) {
        $object->setValue($valueName, null);
      }
      // translate the value
      for ($i=0, $count=sizeof($translations); $i<$count; $i++) {
        $curValueName = $translations[$i]->getAttribute();
        if ($curValueName == $valueName) {
          $translation = $translations[$i]->getTranslation();
          if (!($useDefaults && strlen($translation) == 0)) {
            $object->setValue($valueName, $translation);
          }
          break;
        }
      }
    }
  }

  /**
   * Save translated values for the given object
   * @param object The object to save the translations on
   * @param valueName The name of the value to translate
   * @param existingTranslations An array of already existing translation instances for the object.
   * @param lang The language of the translations.
   * @param saveEmptyValues True/False wether to also save empty translations or not.
   */
  private function saveTranslatedValue(PersistentObject $object, $valueName, array $existingTranslations, $lang, $saveEmptyValues) {
    $inputType = $object->getValueProperty($valueName, 'input_type');
    $inputTypes = self::getIncludedInputTypes();
    if (in_array($inputType, $inputTypes)) {
      $value = $object->getValue($valueName);
      if ($saveEmptyValues || strlen($value) > 0) {
        $translation = null;

        // check if a translation already exists
        for ($i=0, $count=sizeof($existingTranslations); $i<$count; $i++) {
          $curValueName = $existingTranslations[$i]->getAttribute();
          if ($curValueName == $valueName) {
            $translation = &$existingTranslations[$i];
            break;
          }
        }

        // if not, create a new translation
        if ($translation == null) {
          $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
          $translation = $persistenceFacade->create(self::getTranslationType());
        }

        // set all required properties
        $translation->setObjectid($object->getOID()->__toString());
        $translation->setAttribute($valueName);
        $translation->setTranslation($object->getValue($valueName));
        $translation->setLanguage($lang);
      }
    }
  }
}
?>
