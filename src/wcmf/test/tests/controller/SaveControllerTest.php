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
 * SaveControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveControllerTest extends ControllerTestCase {

  const TEST_TYPE1 = 'User';
  const TEST_TYPE2 = 'Book';
  const TEST_OID1 = 'User:0';
  const TEST_OID2 = 'Book:301';

  protected function getControllerName() {
    return 'wcmf\application\controller\SaveController';
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
      'Book' => array(
        array('id' => 301, 'title' => 'title [en]', 'description' => 'description [en]', 'year' => ''),
      ),
      'Lock' => array(
      ),
      'Translation' => array(
      ),
    ));
  }

  /**
   * @group controller
   */
  public function testSave() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID1);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a simple update call
    $testObj = $persistenceFacade->load($oid);
    $persistenceFacade->getTransaction()->detach($testObj->getOID());
    $testObj->setValue('name', 'AdministratorModified');
    $data = array(
      $oid->__toString() => $testObj
    );
    $response = $this->runRequest('update', $data);

    // test
    $obj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $this->assertEquals('AdministratorModified', $obj->getValue('name'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testSaveTranslation() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID2);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a translate call
    $testObj = $persistenceFacade->load($oid);
    $persistenceFacade->getTransaction()->detach($testObj->getOID());
    $testObj->setValue('title', 'title [de]');
    $data = array(
      $oid->__toString() => $testObj,
      'language' => 'de'
    );
    $response = $this->runRequest('update', $data);

    // test
    $translatedObj = ObjectFactory::getInstance('localization')->loadTranslatedObject($oid, 'de');
    $this->assertEquals('title [de]', $translatedObj->getValue('title'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testInsert() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a simple create call with initial data
    $type = self::TEST_TYPE1;
    $testObj = $persistenceFacade->create($type, BuildDepth::SINGLE);
    $testObj->setValue('login', 'user');
    $testObj->setValue('password', 'user');
    $data = array(
      'className' => self::TEST_TYPE1,
      $testObj->getOid()->__toString() => $testObj
    );
    $response = $this->runRequest('create', $data);

    // test
    $this->_insertOID = $response->getValue('oid');
    $obj = $persistenceFacade->load($this->_insertOID, BuildDepth::SINGLE);
    $this->assertNotNull($obj);
    $this->assertEquals('user', $obj->getValue('login'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testInsertTranslation() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a translate call
    $type = self::TEST_TYPE1;
    $testObj = $persistenceFacade->create($type, BuildDepth::SINGLE);
    $testObj->setValue('login', 'user [de]');
    $testObj->setValue('password', 'user');
    $data = array(
      'className' => self::TEST_TYPE1,
      $testObj->getOid()->__toString() => $testObj,
      'language' => 'de'
    );
    $response = $this->runRequest('create', $data);

    // test (can't insert translations for non existing objects)
    $errors = $response->getErrors();
    $this->assertEquals(1, sizeof($errors));
    $error = $errors[0];
    $this->assertEquals("PARAMETER_INVALID", $error->getCode());

    TestUtil::endSession();
  }
}
?>