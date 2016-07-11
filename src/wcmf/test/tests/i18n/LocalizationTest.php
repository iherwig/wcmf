<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\i18n;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * LocalizationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LocalizationTest extends DatabaseTestCase {

  const EXPECTED_DEFAULT_LANGUAGE_CODE = 'en';
  const EXPECTED_DEFAULT_LANGUAGE_NAME = 'English';
  const TEST_OID1 = 'Book:301';
  const TEST_OID2 = 'Book:302';
  const TRANSLATION_TYPE = 'Translation';

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'config' => ''),
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
      ),
      'Book' => array(
        array('id' => 301, 'title' => 'title [en]', 'description' => 'description [en]', 'year' => ''),
        array('id' => 302, 'title' => '', 'description' => '', 'year' => ''),
      ),
      'Translation' => array(
      ),
    ));
  }

  public function testGetDefaultLanguage() {
    $defaultLanguage = ObjectFactory::getInstance('localization')->getDefaultLanguage();

    $this->assertEquals(self::EXPECTED_DEFAULT_LANGUAGE_CODE, $defaultLanguage,
      "The default language is '".self::EXPECTED_DEFAULT_LANGUAGE_CODE."'");
  }

  public function testGetSupportedLanguages() {
    $languages = ObjectFactory::getInstance('localization')->getSupportedLanguages();

    $this->assertTrue(is_array($languages), "Languages is an array");

    $this->assertTrue(array_key_exists(self::EXPECTED_DEFAULT_LANGUAGE_CODE,
      $languages), "The language '".self::EXPECTED_DEFAULT_LANGUAGE_CODE."' is supported");

    $this->assertEquals(self::EXPECTED_DEFAULT_LANGUAGE_NAME,
        $languages[self::EXPECTED_DEFAULT_LANGUAGE_CODE],
        "The name of '".self::EXPECTED_DEFAULT_LANGUAGE_CODE."' is '".
        self::EXPECTED_DEFAULT_LANGUAGE_NAME."'");
  }

  public function testTranslation() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $transaction->detach($oid);

    // there must be no translation for the object in the translation table
    $oids = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString())));
    $this->assertEquals(0, sizeof($oids),
      "There must be no translation for the object in the translation table");

    // store a translation
    $tmp = clone $testObj;
    $tmp->setValue('title', 'title [de]');
    $tmp->setValue('description', 'description [de]');
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // get a value in the default language
    $transaction->begin();
    $testObjUntranslated = $localization->loadTranslation($testObj, $localization->getDefaultLanguage());
    $this->assertTrue($testObjUntranslated != null,
      "The untranslated object could be retrieved by Localization class");
    $this->assertEquals('title [en]', $testObjUntranslated->getValue('title'),
      "The untranslated title is 'title [en]'");

    // get a value in the translation language
    $testObjTranslated = $localization->loadTranslation($testObj, 'de');
    $this->assertTrue($testObjTranslated != null,
      "The translated object could be retrieved by Localization class");
    $this->assertEquals('title [de]', $testObjTranslated->getValue('title'),
      "The translated title is 'title [de]'");
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testTranslationForNonTranslatableValues() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $transaction->detach($oid);

    // store a translation for an untranslatable value
    $tmp = clone $testObj;
    $tmp->setValue('year', "2012-12-12 [de]");
    $tmp->setValue('title', "title [de]");
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // there must be no translation for the untranslatable value in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString()),
      new Criteria(self::TRANSLATION_TYPE, "attribute", "=", "year")));
    $this->assertEquals(0, sizeof($oids),
      "There must be no translation for the untranslatable value in the translation table");
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testDontCreateEntriesForDefaultLanguage() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $transaction->detach($oid);

    // store a translation in the default language
    $tmp = clone $testObj;
    $localization->saveTranslation($tmp, $localization->getDefaultLanguage());
    $transaction->commit();

    // there must be no translation for the default language in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString())));
    $this->assertEquals(0, sizeof($oids),
      "There must be no translation for the default language in the translation table");
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testSaveEmptyValues() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $transaction->detach($oid);

    // store a translation all values empty
    $tmp = clone $testObj;
    $tmp->clearValues();
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // there must be translations for the untranslated values in the translation table
    $transaction->begin();
    $oids2 = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString())));
    $this->assertTrue(sizeof($oids2) > 0,
      "There must be translations for the untranslated values in the translation table");
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testDontCreateDuplicateEntries() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $transaction->detach($oid);

    // store a translation
    $tmp1 = clone $testObj;
    $localization->saveTranslation($tmp1, 'de');
    $transaction->commit();

    // store a translation a second time
    $transaction->begin();
    $tmp2 = clone $testObj;
    $localization->saveTranslation($tmp2, 'de');
    $transaction->commit();

    // there must be only one entry in the translation table
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString()),
      new Criteria(self::TRANSLATION_TYPE, "attribute", "=", "title")));
    $this->assertEquals(1, sizeof($oids),
      "There must be only one entry in the translation table");
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testTranslationWithDefaults() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $transaction->detach($oid);
    $originalValue = $testObj->getValue('title');

    // store a translation for only one value
    $tmp = clone $testObj;
    $tmp->clearValues();
    $tmp->setValue('description', "description [de]");
    $localization->saveTranslation($tmp, 'de');
    $transaction->commit();

    // get the value in the translation language with loading defaults
    $transaction->begin();
    $testObjTranslated1 = $localization->loadTranslation($testObj, 'de', true);
    $this->assertEquals($originalValue, $testObjTranslated1->getValue('title'),
      "The translated value is the default value");

    // get the value in the translation language without loading defaults
    $testObjTranslated2 = $localization->loadTranslation($testObj, 'de', false);
    $this->assertEquals(0, strlen($testObjTranslated2->getValue('title')),
      "The translated value is empty");
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testDeleteTranslation() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid = ObjectId::parse(self::TEST_OID1);
    $testObj = $persistenceFacade->load($oid);
    $transaction->detach($oid);

    // store a translation in two languages
    $tmp1 = clone $testObj;
    $tmp1->setValue('title', 'title [de]');
    $localization->saveTranslation($tmp1, 'de');
    $tmp2 = clone $testObj;
    $tmp2->setValue('title', 'title [it]');
    $localization->saveTranslation($tmp2, 'it');
    $transaction->commit();

    // delete one translation
    $transaction->begin();
    $localization->deleteTranslation($oid, 'de');
    $transaction->commit();

    // there must be no entry in the translation table for the deleted language
    $transaction->begin();
    $oids1 = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString()),
        new Criteria(self::TRANSLATION_TYPE, "language", "=", "de")));
    $this->assertEquals(0, sizeof($oids1),
      "There must be no entry in the translation table for the deleted language");
    // there must be entries in the translation table for the not deleted language
    $oids2 = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString()),
        new Criteria(self::TRANSLATION_TYPE, "language", "=", "it")));
    $this->assertTrue(sizeof($oids2) > 0,
      "There must be entries in the translation table for the not deleted language");

    // store a translation in two languages
    $tmp1 = clone $testObj;
    $tmp1->setValue('title', 'title [de]');
    $localization->saveTranslation($tmp1, 'de');
    $tmp2 = clone $testObj;
    $tmp2->setValue('title', 'title [it]');
    $localization->saveTranslation($tmp2, 'it');
    $transaction->commit();

    // delete all translations
    $transaction->begin();
    $localization->deleteTranslation($oid);
    $transaction->commit();

    // there must be no entry in the translation table for the object
    $transaction->begin();
    $oids = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "objectid", "=", $oid->__toString())));
    $this->assertEquals(0, sizeof($oids),
      "There must be no entry in the translation table for the object");
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testDeleteLanguage() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $localization = ObjectFactory::getInstance('localization');

    // create a new object
    $transaction->begin();
    $oid1 = ObjectId::parse(self::TEST_OID1);
    $testObj1 = $persistenceFacade->load($oid1);
    $transaction->detach($oid1);

    // store a translation in two languages
    $tmp1 = clone $testObj1;
    $tmp1->setValue('title', 'title [de]');
    $localization->saveTranslation($tmp1, 'de');
    $tmp2 = clone $testObj1;
    $tmp2->setValue('title', 'title [it]');
    $localization->saveTranslation($tmp2, 'it');
    $transaction->commit();

    // create a new object
    $transaction->begin();
    $oid2 = ObjectId::parse(self::TEST_OID2);
    $testObj2 = $persistenceFacade->load($oid2);
    $transaction->detach($oid2);

    // store a translation in two languages
    $tmp3 = clone $testObj2;
    $tmp3->setValue('title', 'title [de]');
    $localization->saveTranslation($tmp3, 'de');
    $tmp4 = clone $testObj2;
    $tmp4->setValue('title', 'title [it]');
    $localization->saveTranslation($tmp4, 'it');
    $transaction->commit();

    // delete one language
    $transaction->begin();
    $localization->deleteLanguage('de');
    $transaction->commit();

    // there must be no entries in the translation table for the deleted language
    $transaction->begin();
    $oids1 = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "language", "=", "de")));
    $this->assertEquals(0, sizeof($oids1),
      "There must be no entries in the translation table for the deleted language");
    // there must be entries in the translation table for the not deleted language
    $oids2 = $persistenceFacade->getOIDs(self::TRANSLATION_TYPE,
            array(new Criteria(self::TRANSLATION_TYPE, "language", "=", "it")));
    $this->assertTrue(sizeof($oids2) > 0,
      "There must be entries in the translation table for the not deleted language");
    $transaction->rollback();

    TestUtil::endSession();
  }
}
?>