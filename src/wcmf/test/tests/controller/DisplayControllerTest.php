<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\controller;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * DisplayControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DisplayControllerTest extends ControllerTestCase {

  const TEST_OID1 = 'User:0';
  const TEST_OID2 = 'UserConfig:0';

  protected function getControllerName() {
    return 'wcmf\application\controller\DisplayController';
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
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
      'UserConfig' => array(
        array('id' => 0, 'key' => 'Key', 'val' => 'Value', 'fk_user_id' => 0),
      ),
      'Translation' => array(
      ),
    ));
  }

  /**
   * @group controller
   */
  public function testSimpleDisplay() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID1);

    // simulate a simple read call
    $data = array(
      'oid' => $oid->__toString()
    );
    $response = $this->runRequest('read', $data);

    // test
    $obj = $response->getValue('object');
    $this->assertEquals('Administrator', $obj->getValue('name'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDisplayTranslation() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID1);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // store a translation
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $testObj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Administrator [de]');
    ObjectFactory::getInstance('localization')->saveTranslation($tmp, 'de');
    $transaction->commit();

    // simulate a localized read call
    $data = array(
      'oid' => $oid->__toString(),
      'language' => 'de'
    );
    $response = $this->runRequest('read', $data);

    // test
    $translatedObj = $response->getValue('object');
    $this->assertEquals('Administrator [de]', $translatedObj->getValue('name'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDisplayTranslationOfReferencedObjects() {
    TestUtil::startSession('admin', 'admin');
    $oid1 = ObjectId::parse(self::TEST_OID1);
    $oid2 = ObjectId::parse(self::TEST_OID2);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // associate objects
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $testObj1 = $persistenceFacade->load($oid1, BuildDepth::SINGLE);
    $testObj2 = $persistenceFacade->load($oid2, BuildDepth::SINGLE);

    // store a translations
    $localization = ObjectFactory::getInstance('localization');
    $tmp1 = clone $testObj1;
    $tmp1->setValue('name', 'Administrator [de]');
    $localization->saveTranslation($tmp1, 'de');
    $tmp2 = clone $testObj2;
    $tmp2->setValue('val', 'Value [de]');
    $localization->saveTranslation($tmp2, 'de');
    $transaction->commit();

    // simulate a localized read call
    $data = array(
      'oid' => $oid1->__toString(),
      'depth' => -1,
      'language' => 'de'
    );
    $response = $this->runRequest('read', $data);

    // test
    $translatedObj = $response->getValue('object');
    $translatedChild = $translatedObj->getFirstChild();
    $this->assertEquals('Value [de]', $translatedChild->getValue('val'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDisplayTranslationOfReferencedValues() {
    $this->markTestIncomplete('This test is not ready to run yet.');

    // TODO: implement for input_type = select#async:ReferencedType, select#func:g_getOIDs|ReferencedType
  }
}
?>