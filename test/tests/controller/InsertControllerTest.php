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

/**
 * InsertControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InsertControllerTest extends ControllerTestCase {

  const TEST_TYPE = 'UserRDB';
  const TEST_CHILD_TYPE = 'Locktable';
  const TEST_NM_CHILD_TYPE = 'RoleRDB';
  const TEST_OID = 'UserRDB:0';

  protected function getControllerName() {
    return 'wcmf\application\controller\InsertController';
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
      self::TEST_TYPE.':' => $testObj
    );
    $response = $this->runRequest('create', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $this->_insertOID = $response->getValue('oid');
    $obj = $persistenceFacade->load($this->_insertOID, BuildDepth::SINGLE);
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
      self::TEST_TYPE.':' => $testObj,
      'language' => 'de'
    );
    $response = $this->runRequest('create', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $this->_insertOID = $response->getValue('oid');
    $translatedObj = ObjectFactory::getInstance('localization')->loadTranslatedObject($this->_insertOID, 'de');
    $this->assertEquals('user [de]', $translatedObj->getValue('login'));

    TestUtil::endSession();
  }
}
?>