<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\i18n;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;

/**
 * Localization defines the interface for storing localized entity instances
 * and retrieving them back.
 *
 * Localization is done against a default language. This means
 * that all entity data in the store is supposed to use the default language
 * except those data stored as translations. Translations maybe done in
 * all supported languages.
 *
 * Language names may conform to ISO 639 language codes, but this is not mandatory.
 * One of the supported languages must be equal to the value of default language.
 *
 * Generally only values are translatable.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Localization {

  /**
   * Get the default language that is used in the store.
   * @return The default language value (e.g. en)
   */
  public function getDefaultLanguage();

  /**
   * Get all supported languages.
   * @return An associative array with the language codes as keys and the names as values.
   */
  public function getSupportedLanguages();

  /**
   * Load a single translated object. The object is always loaded with BuildDepth::SINGLE.
   * @param oid The object id of the object to load the translation for.
   * @param lang The language of the translation to load.
   * @param useDefaults Boolean whether to use the default language values
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
   * @param useDefaults Boolean whether to use the default language values
   *    for untranslated/empty values or not. Optional, default is true.
   * @param recursive Boolean whether to load translations for children too or not.
   *    Optional, default is true. For recursive use, the object must have a getChildren method.
   */
  public function loadTranslation(PersistentObject $object, $lang, $useDefaults=true, $recursive=true);

  /**
   * Save a translation of an entity for a specific language. Only the
   * values that have a non-empty value are considered as translations and stored.
   * @param object An instance of the entity type that holds the translations as values.
   * @param lang The language of the translation.
   * @param saveEmptyValues Boolean whether to save empty translations or not.
   *    Optional, default is false
   * @param recursive Boolean whether to save translations for children too or not.
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
