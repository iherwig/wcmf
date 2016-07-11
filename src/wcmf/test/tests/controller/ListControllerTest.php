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
namespace wcmf\test\tests\controller;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * ListControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListControllerTest extends ControllerTestCase {

  const TEST_OID = 'User:0';

  protected function getControllerName() {
    return 'wcmf\application\controller\ListController';
  }

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
      'Lock' => array(
      ),
      'Translation' => array(
      ),
    ));
  }

  /**
   * @group controller
   */
  public function testSimpleList() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID);

    // simulate a simple list call
    $type = $oid->getType();
    $data = array(
      'className' => $type
    );
    $response = $this->runRequest('list', $data);

    // test
    $objects = $response->getValue('list');
    $filteredObjects = Node::filter($objects, $oid, null, null, null);
    $this->assertEquals(1, sizeof($filteredObjects));
    // can only ask for display values
    $this->assertEquals('admin', $filteredObjects[0]->getValue('login'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function _testDisplayTranslation() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $testObj = $persistenceFacade->load($oid, BuildDepth::SINGLE);

    // store a translation
    $tmp = clone $testObj;
    $tmp->setValue('login', 'test [de]');
    ObjectFactory::getInstance('localization')->saveTranslation($tmp, 'de');
    $persistenceFacade->getTransaction()->commit();

    // simulate a localized display call
    $type = $oid->getType();
    $data = array(
      'className' => $type,
      'language' => 'de'
    );
    $response = $this->runRequest('list', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $objects = $response->getValue('objects');
    $filteredObjects = Node::filter($objects, $oid, null, null, null);
    $this->assertEquals(1, sizeof($filteredObjects));
    // can only ask for display values
    $this->assertEquals('test [de]', $filteredObjects[0]->getValue('login'));

    TestUtil::endSession();
  }
}
?>