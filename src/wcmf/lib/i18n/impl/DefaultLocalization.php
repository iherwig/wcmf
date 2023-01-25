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
namespace wcmf\lib\i18n\impl;

use wcmf\lib\config\Configuration;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\LogTrait;
use wcmf\lib\i18n\Localization;
use wcmf\lib\model\NodeIterator;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceEvent;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PersistentObjectProxy;

/**
 * DefaultLocalization is a Localization implementation that saves translations
 * in the store. Entity instances are localized value by value, where a
 * translation of a value of one instance into a specific language
 * is represented by one instance of the translation entity type (e.g. Translation).
 *
 * The translation entity type must have the attributes 'objectid', 'attribute',
 * 'translation', 'language' with the appropriate getter and setter methods. It
 * is defined calling the Localization::setTranslationType() method.
 *
 * A translation is only stored for values with the tag TRANSLATABLE
 * (see AttributeDescription). This allows to exclude certain values
 * e.g. date values from the translation process by omitting this tag.
 *
 * The default language is defined in the configuration key 'defaultLanguage'
 * in section 'localization'.
 *
 * All languages available for translation are either defined in the configuration
 * section 'languages', where each language has it's own entry: e.g. en = English
 * or in a language entity type (e.g. Language). The language entity type must
 * have the attributes 'code' and 'name' with the appropriate getter and setter
 * methods. It is defined calling the Localization::setLanguageType() method.
 * If translation entity type and configuration section are defined, the
 * configuration section is preferred.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultLocalization implements Localization {
  use LogTrait;

  private $persistenceFacade = null;
  private $configuration = null;
  private $eventManager = null;

  private $supportedLanguages = null;
  private $defaultLanguage = null;
  private $translationType = null;
  private $languageType = null;

  private $translatedObjects = [];
  private $createdTranslations = [];

  private static $isDebugEnabled = false;

  /**
   * Configuration
   * @param $persistenceFacade
   * @param $configuration
   * @param $eventManager
   * @param $defaultLanguage
   * @param $translationType Entity type name
   * @param $languageType Entity type name
   */
  public function __construct(PersistenceFacade $persistenceFacade,
      Configuration $configuration, EventManager $eventManager,
      $defaultLanguage, $translationType, $languageType) {
    self::$isDebugEnabled = self::logger()->isDebugEnabled();

    $this->persistenceFacade = $persistenceFacade;
    $this->configuration = $configuration;
    $this->eventManager = $eventManager;
    $supportedLanguages = $this->getSupportedLanguages();

    if (!isset($supportedLanguages[$defaultLanguage])) {
      throw new ConfigurationException('No supported language equals the default language \''.$defaultLanguage.'\'');
    }
    $this->defaultLanguage = $defaultLanguage;

    if (!$this->persistenceFacade->isKnownType($translationType)) {
      throw new IllegalArgumentException('The translation type \''.$translationType.'\' is unknown.');
    }
    $this->translationType = $translationType;

    if (!$this->persistenceFacade->isKnownType($languageType)) {
      throw new IllegalArgumentException('The language type \''.$languageType.'\' is unknown.');
    }
    $this->languageType = $languageType;
    $this->eventManager->addListener(PersistenceEvent::NAME, [$this, 'afterCreate']);
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->eventManager->removeListener(PersistenceEvent::NAME, [$this, 'afterCreate']);
  }

  /**
   * @see Localization::getDefaultLanguage()
   */
  public function getDefaultLanguage() {
    return $this->defaultLanguage;
  }

  /**
   * @see Localization::getSupportedLanguages()
   * Reads the configuration section 'languages'
   */
  public function getSupportedLanguages() {
    if ($this->supportedLanguages == null) {
      // check if the configuration section exists
      if (($languages = $this->configuration->getSection('languages')) !== false) {
        $this->supportedLanguages = $languages;
      }
      // if not, use the languageType
      else {
        $languages = $this->persistenceFacade->loadObjects($this->languageType, BuildDepth::SINGLE);
        for($i=0, $count=sizeof($languages); $i<$count; $i++) {
          $curLanguage = $languages[$i];
          $this->supportedLanguages[$curLanguage->getCode()] = $curLanguage->getName();
        }
      }
    }
    return $this->supportedLanguages;
  }

  /**
   * @see Localization::loadTranslatedObject()
   */
  public function loadTranslatedObject(ObjectId $oid, $lang, $useDefaults=true) {
    $object = $this->persistenceFacade->load($oid, BuildDepth::SINGLE);

    return $object != null ? $this->loadTranslation($object, $lang, $useDefaults, false) : null;
  }

  /**
   * @see Localization::loadTranslation()
   */
  public function loadTranslation(PersistentObject $object, $lang, $useDefaults=true, $recursive=true, $marker=null) {
    if (self::$isDebugEnabled) {
      self::logger()->debug(($marker != null ? strstr($marker, '@').': ' : '')."Load translation [".$lang."] for: ".$object->getOID());
    }
    $translatedObject = $this->loadTranslationImpl($object, $lang, $useDefaults);

    // mark already translated objects to avoid infinite recursion (the marker value is based on the initial object)
    $marker = $marker == null ? __CLASS__.'@'.$lang.':'.$object->getOID() : $marker;
    $object->setProperty($marker, true);

    // recurse if requested
    if ($recursive) {
      $relations = $object->getMapper()->getRelations();
      foreach ($relations as $relation) {
        if ($relation->getOtherNavigability()) {
          $role = $relation->getOtherRole();
          $relationValue = $object->getValue($role);
          if ($relationValue != null) {
            $isMultivalued = $relation->isMultiValued();
            $relatives = $isMultivalued ? $relationValue : [$relationValue];
            foreach ($relatives as $relative) {
              if (self::$isDebugEnabled) {
                self::logger()->debug(($marker != null ? strstr($marker, '@').': ' : '')."Process relative: ".$relative->getOID());
              }
              // skip proxies
              if (!$relative instanceof PersistentObjectProxy) {
                $translatedRelative =  $relative->getProperty($marker) !== true ?
                    $this->loadTranslation($relative, $lang, $useDefaults, $recursive, $marker) :
                    $this->loadTranslationImpl($relative, $lang, $useDefaults);
                if (self::$isDebugEnabled) {
                  self::logger()->debug(($marker != null ? strstr($marker, '@').': ' : '')."Add relative: ".$relative->getOID());
                }
                $translatedObject->deleteNode($relative, $role);
                $translatedObject->addNode($translatedRelative, $role);
              }
              else {
                if (self::$isDebugEnabled) {
                  self::logger()->debug(($marker != null ? strstr($marker, '@').': ' : '')."Skip proxy relative: ".$relative->getOID());
                }
              }
            }
          }
        }
      }
    }
    return $translatedObject;
  }

  /**
   * Load a translation of a single entity for a specific language.
   * @param $object PersistentObject instance to load the translation into. The object
   *    is supposed to have it's values in the default language.
   * @param $lang The language of the translation to load.
   * @param $useDefaults Boolean whether to use the default language values
   *    for untranslated/empty values or not. Optional, default is true.
   * @return PersistentObject instance
   * @throws IllegalArgumentException
   */
  protected function loadTranslationImpl(PersistentObject $object, $lang, $useDefaults=true) {
    if ($object == null) {
      throw new IllegalArgumentException('Cannot load translation for null');
    }

    $oidStr = $object->getOID()->__toString();

    $cacheKey = $oidStr.'.'.$lang.'.'.$useDefaults;
    if (!isset($this->translatedObjects[$cacheKey])) {
      $translatedObject = $object;

      // load the translations and translate the object for any language
      // different to the default language
      // NOTE: the original object will be detached from the transaction
      if ($lang != $this->getDefaultLanguage()) {
        $transaction = $this->persistenceFacade->getTransaction();
        $transaction->detach($translatedObject->getOID());
        $translatedObject = clone $object;

        $query = new ObjectQuery($this->translationType, __CLASS__.'load_save');
        $tpl = $query->getObjectTemplate($this->translationType);
        $tpl->setValue('objectid', Criteria::asValue('=', $oidStr));
        $tpl->setValue('language', Criteria::asValue('=', $lang));
        $translationInstances = $query->execute(BuildDepth::SINGLE);

        // create map for faster access
        $translations = [];
        foreach ($translationInstances as $translationInstance) {
          $translations[$translationInstance->getValue('attribute')] = $translationInstance->getValue('translation');
        }

        // set the translated values in the object
        $iter = new NodeValueIterator($object, false);
        for($iter->rewind(); $iter->valid(); $iter->next()) {
          $valueName = $iter->key();
          $translation = isset($translations[$valueName]) ? $translations[$valueName] : '';
          $this->setTranslatedValue($translatedObject, $valueName, $translation, $useDefaults);
        }
      }
      $this->translatedObjects[$cacheKey] = $translatedObject;
    }
    return $this->translatedObjects[$cacheKey];
  }

  /**
   * @see Localization::loadTranslation()
   * @note Only values with tag TRANSLATABLE are stored.
   */
  public function saveTranslation(PersistentObject $object, $lang, $recursive=true) {
    $this->saveTranslationImpl($object, $lang);

    // recurse if requested
    if ($recursive) {
      $iterator = new NodeIterator($object);
      foreach($iterator as $oidStr => $obj) {
        if ($obj->getOID() != $object->getOID()) {
          // don't resolve proxies
          if (!($obj instanceof PersistentObjectProxy)) {
            $this->saveTranslation($obj, $lang, $recursive);
          }
        }
      }
    }
  }

  /**
   * Save a translation of a single entity for a specific language. Only the
   * values that have a non-empty value are considered as translations and stored.
   * @param $object An instance of the entity type that holds the translations as values.
   * @param $lang The language of the translation.
   */
  protected function saveTranslationImpl(PersistentObject $object, $lang) {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // save the translations for any other language
    else {
      $object->beforeUpdate();

      // get the existing translations for the requested language
      $query = new ObjectQuery($this->translationType, __CLASS__.'load_save');
      $tpl = $query->getObjectTemplate($this->translationType);
      $tpl->setValue('objectid', Criteria::asValue('=', $object->getOID()->__toString()));
      $tpl->setValue('language', Criteria::asValue('=', $lang));
      $translationInstances = $query->execute(BuildDepth::SINGLE);

      // create map for faster access
      $translations = [];
      foreach ($translationInstances as $translationInstance) {
        $translations[$translationInstance->getValue('attribute')] = $translationInstance;
      }

      // save the translations, ignore pk values
      $pkNames = $object->getMapper()->getPkNames();
      $iter = new NodeValueIterator($object, false);
      for($iter->rewind(); $iter->valid(); $iter->next()) {
        $valueName = $iter->key();
        if (!in_array($valueName, $pkNames)) {
          $curIterNode = $iter->currentNode();
          $translation = isset($translations[$valueName]) ? $translations[$valueName] : null;
          $this->saveTranslatedValue($curIterNode, $valueName, $translation, $lang);
        }
      }
    }
  }

  /**
   * @see Localization::deleteTranslation()
   */
  public function deleteTranslation(ObjectId $oid, $lang=null) {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // delete the translations for any other language
    else {
      // get the existing translations for the requested language or all languages
      $query = new ObjectQuery($this->translationType, __CLASS__.'delete_trans'.($lang != null));
      $tpl = $query->getObjectTemplate($this->translationType);
      $tpl->setValue('objectid', Criteria::asValue('=', $oid->__toString()));
      if ($lang != null) {
        $tpl->setValue('language', Criteria::asValue('=', $lang));
      }
      $translations = $query->execute(BuildDepth::SINGLE);

      // delete the found tranlations
      foreach ($translations as $curTranslation) {
        $curTranslation->delete();
      }
    }
  }

  /**
   * @see Localization::deleteLanguage()
   */
  public function deleteLanguage($lang) {
    // if the requested language is the default language, do nothing
    if ($lang == $this->getDefaultLanguage()) {
      // nothing to do
    }
    // delete the translations for any other language
    else {
      // get the existing translations for the requested language
      $query = new ObjectQuery($this->translationType, __CLASS__.'delete_lang');
      $tpl = $query->getObjectTemplate($this->translationType);
      $tpl->setValue('language', Criteria::asValue('=', $lang));
      $translations = $query->execute(BuildDepth::SINGLE);

      // delete the found tranlations
      foreach ($translations as $curTranslation) {
        $curTranslation->delete();
      }
    }
  }

  /**
   * Set a translated value in the given PersistentObject instance.
   * @param $object The object to set the value on. The object
   *    is supposed to have it's values in the default language.
   * @param $valueName The name of the value to translate
   * @param $translation Translation for the value.
   * @param $useDefaults Boolean whether to use the default language if no
   *    translation is found or not.
   */
  private function setTranslatedValue(PersistentObject $object, $valueName, $translation, $useDefaults) {
    $mapper = $object->getMapper();
    $isTranslatable = $mapper != null && $mapper->hasAttribute($valueName) ? $mapper->getAttribute($valueName)->hasTag('TRANSLATABLE') : false;
    if ($isTranslatable) {
      // empty the value, if the default language values should not be used
      if (!$useDefaults) {
        $object->setValue($valueName, null, true);
      }
      // translate the value
      if (!($useDefaults && strlen($translation) == 0)) {
        $object->setValue($valueName, $translation, true);
      }
    }
  }

  /**
   * Save translated values for the given object
   * @param $object The object to save the translations on
   * @param $valueName The name of the value to translate
   * @param $existingTranslation Existing translation instance for the value (might be null).
   * @param $lang The language of the translations.
   */
  private function saveTranslatedValue(PersistentObject $object, $valueName, $existingTranslation, $lang) {
    $mapper = $object->getMapper();
    $isTranslatable = $mapper != null && $mapper->hasAttribute($valueName) ? $mapper->getAttribute($valueName)->hasTag('TRANSLATABLE') : false;
    if ($isTranslatable) {
      $value = $object->getValue($valueName);
      $translation = $existingTranslation;
      $valueIsEmpty = $value === null || $value === '';

      // if no translation exists and the value is not empty, create a new translation
      if ($translation == null && !$valueIsEmpty) {
        $translation = $this->persistenceFacade->create($this->translationType);
      }

      // if a translation exists and the value is empty, remove the existing translation
      if ($translation != null && $valueIsEmpty) {
        $translation->delete();
        $translation = null;
      }

      if ($translation) {
        // set all required properties
        $oid = $object->getOID()->__toString();
        $translation->setValue('objectid', $oid);
        $translation->setValue('attribute', $valueName);
        $translation->setValue('translation', $value);
        $translation->setValue('language', $lang);

        // store translation for oid update if necessary (see afterCreate())
        if (!isset($this->createdTranslations[$oid])) {
          $this->createdTranslations[$oid] = [];
        }
        $this->createdTranslations[$oid][] = $translation;
      }
    }
  }

  /**
   * Update oids after create
   * @param $event
   */
  public function afterCreate(PersistenceEvent $event) {
    if ($event->getAction() == PersistenceAction::CREATE) {
      $oldOid = $event->getOldOid();
      $oldOidStr = $oldOid != null ? $oldOid->__toString() : null;
      if ($oldOidStr != null && isset($this->createdTranslations[$oldOidStr])) {
        foreach ($this->createdTranslations[$oldOidStr] as $translation) {
          $translation->setValue('objectid', $event->getObject()->getOID()->__toString());
        }
      }
    }
  }
}
?>
