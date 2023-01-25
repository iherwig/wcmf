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
namespace wcmf\test\tests\controller;

use wcmf\test\lib\ArrayDataSet;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * DisplayControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DisplayControllerTest extends \Codeception\Test\Unit {

  const TEST_OID1 = 'User:0';
  const TEST_OID2 = 'UserConfig:0';

  const CONTROLLER = 'wcmf\application\controller\DisplayController';

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ],
      'NMUserRole' => [
        ['fk_user_id' => 0, 'fk_role_id' => 0],
      ],
      'Role' => [
        ['id' => 0, 'name' => 'administrators'],
      ],
      'UserConfig' => [
        ['id' => 0, 'name' => 'Name', 'value' => 'Value', 'fk_user_id' => 0],
      ],
      'Translation' => [
      ],
    ]);
  }

  /**
   * @group controller
   */
  public function testSimpleDisplay() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID1);

    // simulate a simple read call
    $data = [
      'oid' => $oid->__toString()
    ];
    $response = $this->tester->runRequest('read', self::CONTROLLER, $data);

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
    $data = [
      'oid' => $oid->__toString(),
      'language' => 'de'
    ];
    $response = $this->tester->runRequest('read', self::CONTROLLER, $data);

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
    $tmp2->setValue('value', 'Value [de]');
    $localization->saveTranslation($tmp2, 'de');
    $transaction->commit();

    // simulate a localized read call
    $data = [
      'oid' => $oid1->__toString(),
      'depth' => -1,
      'language' => 'de'
    ];
    $response = $this->tester->runRequest('read', self::CONTROLLER, $data);

    // test
    $translatedObj = $response->getValue('object');
    $translatedChild = $translatedObj->getFirstChild();
    $this->assertEquals('Value [de]', $translatedChild->getValue('value'));

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