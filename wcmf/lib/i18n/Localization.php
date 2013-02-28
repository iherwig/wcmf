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
interface Localization {

  /**
   * Get the default language that is used in the store.
   * Reads the key 'defaultLanguage' in the configuation section 'i18n'.
   * @return The default language value (e.g. en)
   */
  public function getDefaultLanguage();

  /**
   * Get all supported languages.
   * @return An associative array with the language codes as keys and the names as values.
   */
  public function getSupportedLanguages();

  /**
   * Get the type name of the translation instances.
   * @return The type name.
   */
  public static function getTranslationType();

  /**
   * Get a newly created instance of the type defined in
   * the key 'type' in the configuration section 'i18n'.
   * @return An instance.
   */
  public static function createTranslationInstance();

  /**
   * Load a single translated object. The object is always loaded with BuildDepth::SINGLE.
   * @param oid The object id of the object to load the translation for.
   * @param lang The language of the translation to load.
   * @param useDefaults True/False wether to use the default language values
   *    for untranslated/empty values or not. Optional, default is true
   * @return A reference to the translated object.
   */
  public function loadTranslatedObject(ObjectId $oid, $lang, $useDefaults=true);

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
  public function loadTranslation(PersistentObject $object, $lang, $useDefaults=true, $recursive=true);

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
  public function saveTranslation(PersistentObject $object, $lang, $saveEmptyValues=false, $recursive=true);

  /**
   * Remove translations for a given entity.
   * @param oid The id of the object
   * @param lang The language of the translation to remove. If null, all translations
   *    will be deleted [default: null]
   */
  public function deleteTranslation(ObjectId $oid, $lang=null);

  /**
   * Delete all translations for a given language.
   * @param lang The language of the translations to remove
   */
  public function deleteLanguage($lang);
}
?>
