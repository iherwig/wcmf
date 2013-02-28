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
use wcmf\lib\model\Node;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

/**
 * AsyncPagingControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListControllerTest extends ControllerTestCase {

  const TEST_OID = 'UserRDB:0';

  protected function getControllerName() {
    return 'wcmf\application\controller\ListController';
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array('id' => 1),
      ),
      'user' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '21232f297a57a5a743894a0e4a801fc3'),
      ),
      'translation' => array(
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
    $this->assertTrue($response->getValue('success'), 'The request was successful');
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