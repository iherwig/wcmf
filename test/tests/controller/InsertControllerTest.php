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

use wcmf\lib\core\ObjectFactory;
use test\lib\TestUtil;
use wcmf\lib\i18n\Localization;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

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

  private $_insertOID = null;

  protected function getControllerName() {
    return 'wcmf\application\controller\InsertController';
  }

  protected function setUp() {
    parent::setUp();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    TestUtil::createTestObject(ObjectId::parse(self::TEST_OID), array());
    $transaction->commit();
    $this->_insertOID = null;
  }

  protected function tearDown() {
    $localization = Localization::getInstance();
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse(self::TEST_OID));
    $localization->deleteTranslation(ObjectId::parse(self::TEST_OID));
    if ($this->_insertOID != null) {
      TestUtil::deleteTestObject($this->_insertOID);
      $localization->deleteTranslation($this->_insertOID);
    }
    $transaction->commit();
    parent::tearDown();
  }

  /**
   * @group controller
   */
  public function testInsert() {
    // simulate a simple insert call with initial data
    $type = self::TEST_TYPE;
    $testObj = ObjectFactory::getInstance('persistenceFacade')->create($type, BuildDepth::SINGLE);
    $testObj->setValue('name', 'Administrator');
    $data = array(
      'className' => self::TEST_TYPE,
      self::TEST_TYPE.':' => $testObj
    );
    $response = $this->runRequest($data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $this->_insertOID = $response->getValue('oid');
    $obj = TestUtil::loadTestObject($this->_insertOID);
    $this->assertEquals('Administrator', $obj->getValue('name'));
  }

  /**
   * @group controller
   */
  public function testInsertTranslation() {
    // simulate a translate call
    $type = self::TEST_TYPE;
    $testObj = ObjectFactory::getInstance('persistenceFacade')->create($type, BuildDepth::SINGLE);
    $testObj->setValue('name', 'Administrator [it]');
    $data = array(
      'className' => self::TEST_TYPE,
      self::TEST_TYPE.':' => $testObj,
      'language' => 'it'
    );
    $response = $this->runRequest($data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $this->_insertOID = $response->getValue('oid');
    $translatedObj = Localization::getInstance()->loadTranslatedObject($this->_insertOID, 'it');
    $this->assertEquals('Administrator [it]', $translatedObj->getValue('name'));
  }
}
?>