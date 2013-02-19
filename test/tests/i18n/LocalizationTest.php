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
namespace test\tests\i18n;

use test\lib\ArrayDataSet;
use test\lib\DatabaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Localization;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;

/**
 * LocalizationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LocalizationTest extends DatabaseTestCase {

  const EXPECTED_DEFAULT_LANGUAGE_CODE = 'en';
  const EXPECTED_DEFAULT_LANGUAGE_NAME = 'English';
  const TEST_OID1 = 'UserRDB:301';
  const TEST_OID2 = 'UserRDB:302';
  const TRANSLATION_TYPE = 'Translation';

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'user' => array(
        array('id' => 301, 'name' => 'Herwig', 'firstname' => 'Ingo'),
        array('id' => 302),
      ),
      'translation' => array(
      ),
    ));
  }

  public function testGetDefaultLanguage() {
    $defaultLanguage = Localization::getInstance()->getDefaultLanguage();

    $this->assertEquals(self::EXPECTED_DEFAULT_LANGUAGE_CODE, $defaultLanguage,
      "The default language is '".self::EXPECTED_DEFAULT_LANGUAGE_CODE."'");
  }

  public function testGetSupportedLanguages() {
    $languages = Localization::getInstance()->getSupportedLanguages();

    $this->assertTrue(is_array($languages), "Languages is an array");

    $this->assertTrue(array_key_exists(self::EXPECTED_DEFAULT_LANGUAGE_CODE,
      $languages), "The language '".self::EXPECTED_DEFAULT_LANGUAGE_CODE."' is supported");

    $this->assertEquals(self::EXPECTED_DEFAULT_LANGUAGE_NAME,
        $languages[self::EXPECTED_DEFAULT_LANGUAGE_CODE],
        "The name of '".self::EXPECTED_DEFAULT_LANGUAGE_CODE."' is '".
        self::EXPECTED_DEFAULT_LANGUAGE_NAME."'");
  }

  public function testCreateTranslationInstance() {
    $instance = Localization::createTranslationInstance();
    $this->assertEquals(self::TRANSLATION_TYPE, $instance->getType(),
      "The translation type is '".self::TRANSLATION_TYPE."'");
  }

  public function testTranslation() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $translationType = Localization::getTranslationType();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);

    // there must be no translation for the object in the translation table
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString())));
    $this->assertEquals(0, sizeof($oids),
      "There must be no translation for the object in the translation table");

    // store a translation
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [de]');
    $tmp->setValue('firstname', 'Ingo [de]');
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // get a value in the default language
    $transaction->begin();
    $testObjUntranslated = clone $testObj;
    $localization->loadTranslation($testObjUntranslated, $localization->getDefaultLanguage());
    $this->assertTrue($testObjUntranslated != null,
      "The untranslated object could be retrieved by Localization class");
    $this->assertEquals('Herwig', $testObjUntranslated->getValue('name'),
      "The untranslated name is 'Herwig'");

    // get a value in the translation language
    $testObjTranslated = clone $testObj;
    $localization->loadTranslation($testObjTranslated, 'de');
    $this->assertTrue($testObjTranslated != null,
      "The translated object could be retrieved by Localization class");
    $this->assertEquals('Herwig [de]', $testObjTranslated->getValue('name'),
      "The translated name is 'Herwig [de]'");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testTranslationForNonTranslatableValues() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $translationType = Localization::getTranslationType();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $originalValue = $testObj->getValue('name');

    // set the input type of an attribute to a not translatable type
    $testObj->setValueProperty('name', 'input_type', 'notTranslatable');

    // store a translation for an untranslatable value
    $tmp = clone $testObj;
    $tmp->setValue('name', "Herwig [de]");
    $tmp->setValue('firstname', "Ingo [de]");
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // there must be no translation for the untranslatable value in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString()),
      new Criteria($translationType, "attribute", "=", "name")));
    $this->assertEquals(0, sizeof($oids),
      "There must be no translation for the untranslatable value in the translation table");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testDontCreateEntriesForDefaultLanguage() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $translationType = Localization::getTranslationType();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);

    // store a translation in the default language with saveEmptyValues = true
    $tmp = clone $testObj;
    $localization->saveTranslation($tmp, $localization->getDefaultLanguage(), true);
    $transaction->commit();

    // there must be no translation for the default language in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString())));
    $this->assertEquals(0, sizeof($oids),
      "There must be no translation for the default language in the translation table");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testDontSaveUntranslatedValues() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $translationType = Localization::getTranslationType();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);

    // store a translation all values empty and saveEmptyValues = false
    $tmp = clone $testObj;
    $tmp->clearValues();
    $localization->saveTranslation($tmp, 'de', false);
    $transaction->commit();

    // there must be no translation for the untranslated values in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString())));
    $this->assertEquals(0, sizeof($oids),
      "There must be no translation for the untranslated values in the translation table");

    // store a translation all values empty and saveEmptyValues = true
    $localization->saveTranslation($tmp, 'de', true);
    $transaction->commit();

    // there must be translations for the untranslated values in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString())));
    $this->assertTrue(sizeof($oids) > 0,
      "There must be translations for the untranslated values in the translation table");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testDontCreateDuplicateEntries() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $translationType = Localization::getTranslationType();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);

    // store a translation
    $tmp = clone $testObj;
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // store a translation a second time
    $transaction->begin();
    $tmp = clone $testObj;
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // there must be only one entry in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString()),
      new Criteria($translationType, "attribute", "=", "name")));
    $this->assertEquals(1, sizeof($oids),
      "There must be only one entry in the translation table");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testTranslationWithDefaults() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $originalValue = $testObj->getValue('name');

    // store a translation for only one value
    $tmp = clone $testObj;
    $tmp->clearValues();
    $tmp->setValue('firstname', "Ingo [de]");
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // get the value in the translation language with loading defaults
    $transaction->begin();
    $testObjTranslated = clone $testObj;
    $localization->loadTranslation($testObjTranslated, 'de', true);
    $this->assertEquals($originalValue, $testObjTranslated->getValue('name'),
      "The translated value is the default value");

    // get the value in the translation language without loading defaults
    $testObjTranslated = clone $testObj;
    $localization->loadTranslation($testObjTranslated, 'de', false);
    $this->assertEquals(0, strlen($testObjTranslated->getValue('name')),
      "The translated value is empty");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testDeleteTranslation() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $translationType = Localization::getTranslationType();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);

    // store a translation in two languages
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [de]');
    $localization->saveTranslation($tmp, 'de', true);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [it]');
    $localization->saveTranslation($tmp, 'it', true);
    $transaction->commit();

    // delete one translation
    $transaction->begin();
    $localization->deleteTranslation($oid, 'de');
    $transaction->commit();

    // there must be no entry in the translation table for the deleted language
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString()),
        new Criteria($translationType, "language", "=", "de")));
    $this->assertEquals(0, sizeof($oids),
      "There must be no entry in the translation table for the deleted language");
    // there must be entries in the translation table for the not deleted language
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString()),
        new Criteria($translationType, "language", "=", "it")));
    $this->assertTrue(sizeof($oids) > 0,
      "There must be entries in the translation table for the not deleted language");

    // store a translation in two languages
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [de]');
    $localization->saveTranslation($tmp, 'de', true);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [it]');
    $localization->saveTranslation($tmp, 'it', true);
    $transaction->commit();

    // delete all translations
    $transaction->begin();
    $localization->deleteTranslation($oid);
    $transaction->commit();

    // there must be no entry in the translation table for the object
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "objectid", "=", $oid->__toString())));
    $this->assertEquals(0, sizeof($oids),
      "There must be no entry in the translation table for the object");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testDeleteLanguage() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $translationType = Localization::getTranslationType();
    $localization = Localization::getInstance();

    // create a new object
    $transaction->begin();
    $oid1 = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid1);

    // store a translation in two languages
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [de]');
    $localization->saveTranslation($tmp, 'de', true);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [it]');
    $localization->saveTranslation($tmp, 'it', true);
    $transaction->commit();

    // create a new object
    $transaction->begin();
    $oid2 = ObjectId::parse(self::TEST_OID2);
    $testObj = $persistenceFacade->load($oid2);

    // store a translation in two languages
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [de]');
    $localization->saveTranslation($tmp, 'de', true);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [it]');
    $localization->saveTranslation($tmp, 'it', true);
    $transaction->commit();

    // delete one language
    $transaction->begin();
    $localization->deleteLanguage('de');
    $transaction->commit();

    // there must be no entries in the translation table for the deleted language
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "language", "=", "de")));
    $this->assertEquals(0, sizeof($oids),
      "There must be no entries in the translation table for the deleted language");
    // there must be entries in the translation table for the not deleted language
    $oids = $persistenceFacade->getOIDs($translationType, array(new Criteria($translationType, "language", "=", "it")));
    $this->assertTrue(sizeof($oids) > 0,
      "There must be entries in the translation table for the not deleted language");
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }
}
?>