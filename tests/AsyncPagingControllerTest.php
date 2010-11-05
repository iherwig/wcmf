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
 * $Id: AsyncPagingControllerTest.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once("lib/ControllerTestCase.php");

/**
 * @class AsyncPagingControllerTest
 * @ingroup test
 * @brief AsyncPagingControllerTest tests the DisplayController.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AsyncPagingControllerTest extends ControllerTestCase
{
  const TEST_OID = 'UserRDB:0';

  protected function getControllerName()
  {
    return 'AsyncPagingController';
  }

  public function testSimpleList()
  {
    $oid = ObjectId::parse(AsyncPagingControllerTest::TEST_OID);
    $this->createTestObject($oid, array('login' => 'test'));

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
    $this->deleteTestObject($oid);
  }

  public function _testDisplayTranslation()
  {
    $oid = ObjectId::parse(AsyncPagingControllerTest::TEST_OID);
    $testObj = $this->createTestObject($oid, array('login' => 'test'));

    // store a translation
    $tmp = &$testObj->duplicate();
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
    $this->deleteTestObject($oid);
    Localization::deleteTranslation($oid);
  }
}
?>