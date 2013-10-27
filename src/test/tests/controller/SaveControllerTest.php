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

use test\lib\ArrayDataSet;
use test\lib\ControllerTestCase;
use test\lib\TestUtil;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

/**
 * SaveControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveControllerTest extends ControllerTestCase {

  const TEST_OID = 'UserRDB:0';
  const TEST_TYPE = 'UserRDB';

  protected function getControllerName() {
    return 'wcmf\application\controller\SaveController';
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array('id' => 1),
      ),
      'user' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm'),
      ),
      'locktable' => array(
      ),
      'role' => array(
      ),
      'translation' => array(
      ),
    ));
  }

  /**
   * @group controller
   */
  public function testSave() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a simple update call
    $testObj = $persistenceFacade->load($oid);
    $persistenceFacade->getTransaction()->detach($testObj);
    $testObj->setValue('name', 'AdministratorModified');
    $data = array(
      $oid->__toString() => $testObj
    );
    $response = $this->runRequest('update', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $obj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $this->assertEquals('AdministratorModified', $obj->getValue('name'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testSaveTranslation() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a translate call
    $testObj = $persistenceFacade->load($oid);
    $persistenceFacade->getTransaction()->detach($testObj);
    $testObj->setValue('name', 'Administrator [de]');
    $data = array(
      $oid->__toString() => $testObj,
      'language' => 'de'
    );
    $response = $this->runRequest('update', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $translatedObj = ObjectFactory::getInstance('localization')->loadTranslatedObject($oid, 'de');
    $this->assertEquals('Administrator [de]', $translatedObj->getValue('name'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testInsert() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a simple create call with initial data
    $type = self::TEST_TYPE;
    $testObj = $persistenceFacade->create($type, BuildDepth::SINGLE);
    $testObj->setValue('login', 'user');
    $testObj->setValue('password', 'user');
    $data = array(
      'className' => self::TEST_TYPE,
      $testObj->getOid()->__toString() => $testObj
    );
    $response = $this->runRequest('create', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
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
    $type = self::TEST_TYPE;
    $testObj = $persistenceFacade->create($type, BuildDepth::SINGLE);
    $testObj->setValue('login', 'user [de]');
    $testObj->setValue('password', 'user');
    $data = array(
      'className' => self::TEST_TYPE,
      $testObj->getOid()->__toString() => $testObj,
      'language' => 'de'
    );
    $response = $this->runRequest('create', $data);

    // test (can't insert translations for non existing objects)
    $this->assertFalse($response->getValue('success'), 'The request was not successful');
    $errors = $response->getErrors();
    $this->assertEquals(1, sizeof($errors));
    $error = $errors[0];
    $this->assertEquals("PARAMETER_INVALID", $error->getCode());

    TestUtil::endSession();
  }
}
?>