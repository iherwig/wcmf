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
namespace test\tests\controller;

use test\lib\ControllerTestCase;
use test\lib\TestUtil;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Localization;
use wcmf\lib\persistence\ObjectId;

/**
 * DisplayControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DisplayControllerTest extends ControllerTestCase {

  const TEST_OID1 = 'UserRDB:0';
  const TEST_OID2 = 'Locktable:0';

  protected function getControllerName() {
    return 'wcmf\application\controller\DisplayController';
  }

  protected function setUp() {
    parent::setUp();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    TestUtil::createTestObject(ObjectId::parse(self::TEST_OID1), array('name' => 'Administrator'));
    TestUtil::createTestObject(ObjectId::parse(self::TEST_OID2), array('sessionid' => 'Session Id'));
    $transaction->commit();
  }

  protected function tearDown() {
    $localization = Localization::getInstance();
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse(self::TEST_OID1));
    TestUtil::deleteTestObject(ObjectId::parse(self::TEST_OID2));
    $localization->deleteTranslation(ObjectId::parse(self::TEST_OID1));
    $localization->deleteTranslation(ObjectId::parse(self::TEST_OID2));
    $transaction->commit();
    parent::tearDown();
  }

  /**
   * @group controller
   */
  public function testSimpleDisplay() {
    $oid = ObjectId::parse(self::TEST_OID1);

    // simulate a simple display call
    $data = array(
      'oid' => $oid->__toString()
    );
    $response = $this->runRequest($data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $obj = $response->getValue('object');
    $this->assertEquals('Administrator', $obj->getValue('name'));
  }

  /**
   * @group controller
   */
  public function testDisplayTranslation() {
    $oid = ObjectId::parse(self::TEST_OID1);

    // store a translation
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $testObj = TestUtil::loadTestObject($oid);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Administrator [de]');
    Localization::getInstance()->saveTranslation($tmp, 'de');
    $transaction->commit();

    // simulate a localized display call
    $data = array(
      'oid' => $oid->__toString(),
      'language' => 'de'
    );
    $response = $this->runRequest($data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $translatedObj = $response->getValue('object');
    $this->assertEquals('Administrator [de]', $translatedObj->getValue('name'));
  }

  /**
   * @group controller
   */
  public function testDisplayTranslationOfReferencedObjects() {
    $oid1 = ObjectId::parse(self::TEST_OID1);
    $oid2 = ObjectId::parse(self::TEST_OID2);

    // associate objects
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $testObj1 = TestUtil::loadTestObject($oid1);
    $testObj2 = TestUtil::loadTestObject($oid2);
    $testObj1->addNode($testObj2);

    // store a translations
    $localization = Localization::getInstance();
    $tmp1 = clone $testObj1;
    $tmp1->setValue('name', 'Administrator [de]');
    $localization->saveTranslation($tmp1, 'de');
    $tmp2 = clone $testObj2;
    $tmp2->setValue('sessionid', 'Session Id [de]');
    $localization->saveTranslation($tmp2, 'de');
    $transaction->commit();

    // simulate a localized display call
    $data = array(
      'oid' => $oid1->__toString(),
      'depth' => -1,
      'language' => 'de'
    );
    $response = $this->runRequest($data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $translatedObj = $response->getValue('object');
    $translatedChild = $translatedObj->getFirstChild();
    $this->assertEquals('Session Id [de]', $translatedChild->getValue('sessionid'));
  }

  /**
   * @group controller
   */
  public function testDisplayTranslationOfReferencedValues() {
    $this->markTestIncomplete('This test is not ready to run yet.');

    // TODO: implement for input_type = select#async:ReferencedType, select#fkt:g_getOIDs|ReferencedType
  }
}
?>