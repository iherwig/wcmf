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
 * $Id: LocalizationTest.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once(BASE."wcmf/lib/i18n/class.Localization.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");

/**
 * @class LocalizationTest
 * @ingroup test
 * @brief LocalizationTest tests the localization.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LocalizationTest extends WCMFTestCase
{
  const EXPECTED_DEFAULT_LANGUAGE_CODE = 'en';
  const EXPECTED_DEFAULT_LANGUAGE_NAME = 'English';
  const TEST_OID1 = 'UserRDB:-1';
  const TEST_OID2 = 'UserRDB:-2';
  const TRANSLATION_TYPE = 'Translation';

  public function testGetDefaultLanguage()
  {
    $defaultLanguage = Localization::getDefaultLanguage();

    $this->assertTrue($defaultLanguage == LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_CODE,
      "The default language is '".LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_CODE."'");
  }

  public function testGetSupportedLanguages()
  {
    $languages = Localization::getSupportedLanguages();

    $this->assertTrue(is_array($languages), "Languages is an array");

    $this->assertTrue(array_key_exists(LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_CODE,
      $languages), "The language '".LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_CODE."' is supported");

    $this->assertTrue($languages[LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_CODE] ==
      LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_NAME,
        "The name of '".LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_CODE."' is '".
        LocalizationTest::EXPECTED_DEFAULT_LANGUAGE_NAME."'");
  }

  public function testCreateTranslationInstance()
  {
    $instance = &Localization::createTranslationInstance();
    $this->assertTrue($instance->getType() == LocalizationTest::TRANSLATION_TYPE,
      "The translation type is '".LocalizationTest::TRANSLATION_TYPE."'");
  }

  public function testTranslation()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = $this->createTestObject($oid);

    // there must be no translation for the object in the translation table
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(),
      "objectid = '".$oid."'");
    $this->assertTrue(sizeof($oids) == 0,
      "There must be no translation for the object in the translation table");

    // store a translation
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [de]');
    $tmp->setValue('firstname', 'Ingo [de]');
    Localization::saveTranslation($tmp, 'de');

    // get a value in the default language
    $testObjUntranslated = $testObj->duplicate();
    Localization::loadTranslation($testObjUntranslated, Localization::getDefaultLanguage());
    $this->assertTrue($testObjUntranslated != null,
      "The untranslated object could be retrieved by Localization class");
    $this->assertTrue($testObjUntranslated->getValue('name') == 'Herwig',
      "The untranslated name is 'Herwig'");

    // get a value in the translation language
    $testObjTranslated = $testObj->duplicate();
    Localization::loadTranslation($testObjTranslated, 'de');
    $this->assertTrue($testObjTranslated != null,
      "The translated object could be retrieved by Localization class");
    $this->assertTrue($testObjTranslated->getValue('name') == 'Herwig [de]',
      "The translated name is 'Herwig [de]'");

    // cleanup
    $this->deleteTestObject($oid);
    $this->runAnonymous(false);
  }

  public function testTranslationForNonTranslatableValues()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = $this->createTestObject($oid);
    $originalValue = $testObj->getValue('name');

    // set the input type of an attribute to a not translatable type
    $testObj->setValueProperty('name', 'input_type', 'notTranslatable');

    // store a translation for an untranslatable value
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', "Herwig [de]");
    $tmp->setValue('firstname', "Ingo [de]");
    Localization::saveTranslation($tmp, 'de');

    // there must be no translation for the untranslatable value in the translation table
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".
      $oid."' AND attribute = 'name'");
    $this->assertTrue(sizeof($oids) == 0,
      "There must be no translation for the untranslatable value in the translation table");

    // cleanup
    $this->deleteTestObject($oid);
    $this->runAnonymous(false);
  }

  public function testDontCreateEntriesForDefaultLanguage()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = $this->createTestObject($oid);

    // store a translation in the default language with saveEmptyValues = true
    $tmp = $testObj->duplicate();
    Localization::saveTranslation($tmp, Localization::getDefaultLanguage(), true);

    // there must be no translation for the default language in the translation table
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".$oid."'");
    $this->assertTrue(sizeof($oids) == 0,
      "There must be no translation for the default language in the translation table");

    // cleanup
    $this->deleteTestObject($oid);
    $this->runAnonymous(false);
  }

  public function testDontSaveUntranslatedValues()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = &$this->createTestObject($oid);

    // store a translation all values empty and saveEmptyValues = false
    $tmp = $testObj->duplicate();
    $tmp->clearValues();
    Localization::saveTranslation($tmp, 'de', false);

    // there must be no translation for the untranslated values in the translation table
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".$oid."'");
    $this->assertTrue(sizeof($oids) == 0,
      "There must be no translation for the untranslated values in the translation table");

    // store a translation all values empty and saveEmptyValues = true
    Localization::saveTranslation($tmp, 'de', true);

    // there must be translations for the untranslated values in the translation table
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".$oid."'");
    $this->assertTrue(sizeof($oids) > 0,
      "There must be translations for the untranslated values in the translation table");

    // cleanup
    $this->deleteTestObject($oid);
    $this->runAnonymous(false);
  }

  public function testDontCreateDuplicateEntries()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = $this->createTestObject($oid);

    // store a translation
    $tmp = $testObj->duplicate();
    Localization::saveTranslation($tmp, 'de');

    // store a translation a second time
    $tmp = $testObj->duplicate();
    Localization::saveTranslation($tmp, 'de');

    // there must be only one entry in the translation table
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".$oid."' AND attribute = 'name'");
    $this->assertTrue(sizeof($oids) == 1,
      "There must be only one entry in the translation table");

    // cleanup
    $this->deleteTestObject($oid);
    $this->runAnonymous(false);
  }

  public function testTranslationWithDefaults()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = $this->createTestObject($oid);
    $originalValue = $testObj->getValue('name');

    // store a translation for only one value
    $tmp = $testObj->duplicate();
    $tmp->clearValues();
    $tmp->setValue('firstname', "Ingo [de]");
    Localization::saveTranslation($tmp, 'de');

    // get the value in the translation language with loading defaults
    $testObjTranslated = $testObj->duplicate();
    Localization::loadTranslation($testObjTranslated, 'de', true);
    $this->assertTrue($testObjTranslated->getValue('name') == $originalValue,
      "The translated value is the default value");

    // get the value in the translation language without loading defaults
    $testObjTranslated = $testObj->duplicate();
    Localization::loadTranslation($testObjTranslated, 'de', false);
    $this->assertTrue(strlen($testObjTranslated->getValue('name')) == 0,
      "The translated value is empty");

    // cleanup
    $this->deleteTestObject($oid);
    $this->runAnonymous(false);
  }

  public function testDeleteTranslation()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = &$this->createTestObject($oid);

    // store a translation in two languages
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [de]');
    Localization::saveTranslation($tmp, 'de', true);
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [it]');
    Localization::saveTranslation($tmp, 'it', true);

    // delete one translation
    Localization::deleteTranslation($oid, 'de');
    // there must be no entry in the translation table for the deleted language
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".$oid."' AND language = 'de'");
    $this->assertTrue(sizeof($oids) == 0,
      "There must be no entry in the translation table for the deleted language");
    // there must be entries in the translation table for the not deleted language
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".$oid."' AND language = 'it'");
    $this->assertTrue(sizeof($oids) > 0,
      "There must be entries in the translation table for the not deleted language");

    // store a translation in two languages
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [de]');
    Localization::saveTranslation($tmp, 'de', true);
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [it]');
    Localization::saveTranslation($tmp, 'it', true);
    // delete all translations
    Localization::deleteTranslation($oid);
    // there must be no entry in the translation table for the object
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "objectid = '".$oid."'");
    $this->assertTrue(sizeof($oids) == 0,
      "There must be no entry in the translation table for the object");

    // cleanup
    $this->deleteTestObject($oid);
    $this->runAnonymous(false);
  }

  public function testDeleteLanguage()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // create a new object
    $oid1 = ObjectId::parse(LocalizationTest::TEST_OID1);
    $testObj = $this->createTestObject($oid1);

    // store a translation in two languages
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [de]');
    Localization::saveTranslation($tmp, 'de', true);
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [it]');
    Localization::saveTranslation($tmp, 'it', true);

    // create a new object
    $oid2 = ObjectId::parse(LocalizationTest::TEST_OID2);
    $testObj = $this->createTestObject($oid2);

    // store a translation in two languages
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [de]');
    Localization::saveTranslation($tmp, 'de', true);
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [it]');
    Localization::saveTranslation($tmp, 'it', true);

    // delete one language
    Localization::deleteLanguage('de');
    // there must be no entries in the translation table for the deleted language
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "language = 'de'");
    $this->assertTrue(sizeof($oids) == 0,
      "There must be no entries in the translation table for the deleted language");
    // there must be entries in the translation table for the not deleted language
    $oids = $persistenceFacade->getOIDs(Localization::getTranslationType(), "language = 'it'");
    $this->assertTrue(sizeof($oids) > 0,
      "There must be entries in the translation table for the not deleted language");

    // cleanup
    $this->deleteTestObject($oid1);
    $this->deleteTestObject($oid2);
    $this->runAnonymous(false);
  }

  /**
   * Helper methods
   */

  protected function createTestObject($oid)
  {
    $object = parent::createTestObject($oid, array('name' => 'Herwig', 'firstname' => 'Ingo'));
    return $object;
  }

  protected function deleteTestObject($oid)
  {
    parent::deleteTestObject($oid);

    // delete translations
    $persistenceFacade = PersistenceFacade::getInstance();
    $query = PersistenceFacade::createObjectQuery(Localization::getTranslationType());
    $tpl = $query->getObjectTemplate(Localization::getTranslationType());
    $tpl->setObjectid($oid);
    $oids = $query->execute(false);
    foreach ($oids as $curOID) {
      $persistenceFacade->delete($curOID);
    }
  }
}
?>