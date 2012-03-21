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
use wcmf\lib\i18n\Localization;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\ObjectId;

/**
 * AsyncPagingControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AsyncPagingControllerTest extends ControllerTestCase {

  const TEST_OID = 'UserRDB:0';

  protected function getControllerName() {
    return 'AsyncPagingController';
  }

  /**
   * @group controller
   */
  public function testSimpleList() {
    $this->markTestIncomplete('This test is not ready to run yet.');

    $oid = ObjectId::parse(AsyncPagingControllerTest::TEST_OID);
    TestUtil::createTestObject($oid, array('login' => 'test'));

    // simulate a simple list call
    $type = $oid->getType();
    $data = array(
      'type' => $type
    );
    $response = $this->runRequest($data);

    // test
    $objects = &$response->getValue('objects');
    $filteredObjects = Node::filter($objects, $oid, null, null, null);
    // can only ask for display values
    $this->assertTrue($filteredObjects[0]->getValue('login') == 'test', "The login is 'test'");

    // cleanup
    TestUtil::deleteTestObject($oid);
  }

  /**
   * @group controller
   */
  public function _testDisplayTranslation() {
    $this->markTestIncomplete('This test is not ready to run yet.');

    $oid = ObjectId::parse(AsyncPagingControllerTest::TEST_OID);
    $testObj = TestUtil::createTestObject($oid, array('login' => 'test'));

    // store a translation
    $tmp = clone $testObj;
    $tmp->setValue('login', 'test [de]');
    Localization::saveTranslation($tmp, 'de');

    // simulate a localized display call
    $type = $oid->getType();
    $data = array(
      'type' => $type,
      'language' => 'de'
    );
    $response = $this->runRequest($data);

    // test
    $objects = &$response->getValue('objects');
    $filteredObjects = Node::filter($objects, $oid, null, null, null);
    // can only ask for display values
    $this->assertTrue($filteredObjects[0]->getValue('login') == 'test [de]',
      "The translated login is 'test [de]'");

    // cleanup
    TestUtil::deleteTestObject($oid);
    Localization::deleteTranslation($oid);
  }
}
?>