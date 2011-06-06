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
 * $Id: DisplayControllerTest.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once("lib/ControllerTestCase.php");

/**
 * @class DisplayControllerTest
 * @ingroup test
 * @brief DisplayControllerTest tests the DisplayController.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DisplayControllerTest extends ControllerTestCase
{
  const TEST_OID1 = 'UserRDB:0';
  const TEST_OID2 = 'Locktable:0';

  protected function getControllerName()
  {
    return 'DisplayController';
  }

  /**
   * @group controller
   */
  public function testSimpleDisplay()
  {
    $this->markTestIncomplete('This test is not ready to run yet.');

    $oid = ObjectId::parse(DisplayControllerTest::TEST_OID1);
    TestUtil::createTestObject($oid, array('name' => 'Administrator'));

    // simulate a simple display call
    $type = $oid->getType();
    $data = array(
      'oid' => $oid->__toString()
    );
    $response = $this->runRequest($data);

    // test
    $obj = &$response->getValue('node');
    $this->assertTrue($obj->getValue('name') == 'Administrator', "The name is 'Administrator'");

    // cleanup
    TestUtil::deleteTestObject($oid);
  }

  /**
   * @group controller
   */
  public function testDisplayTranslation()
  {
    $this->markTestIncomplete('This test is not ready to run yet.');

    $oid = ObjectId::parse(DisplayControllerTest::TEST_OID1);
    $testObj = TestUtil::createTestObject($oid, array('name' => 'Administrator'));

    // store a translation
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Administrator [de]');
    Localization::saveTranslation($tmp, 'de');

    // simulate a localized display call
    $data = array(
      'oid' => $oid->__toString(),
      'language' => 'de'
    );
    $response = $this->runRequest($data);

    // test
    $translatedObj = $response->getValue('node');
    $this->assertTrue($translatedObj->getValue('name') == 'Administrator [de]',
      "The translated name is 'Administrator [de]'");

    // cleanup
    TestUtil::deleteTestObject($oid);
    Localization::deleteTranslation($oid);
  }

  /**
   * @group controller
   */
  public function testDisplayTranslationOfReferencedObjects()
  {
    $this->markTestIncomplete('This test is not ready to run yet.');

    $oid1 = ObjectId::parse(DisplayControllerTest::TEST_OID1);
    $oid2 = ObjectId::parse(DisplayControllerTest::TEST_OID2);
    $testObj1 = &TestUtil::createTestObject($oid1, array('name' => 'Administrator'));
    $testObj2 = &TestUtil::createTestObject($oid2, array('sessionid' => 'Session Id'));
    $testObj1->addNode($testObj2);
    $testObj2->save();

    // store a translations
    $tmp = clone $testObj1;
    $tmp->setValue('name', 'Administrator [de]');
    Localization::saveTranslation($tmp, 'de');
    $tmp = clone $testObj2;
    $tmp->setValue('sessionid', 'Session Id [de]');
    Localization::saveTranslation($tmp, 'de');

    // simulate a localized display call
    $data = array(
      'oid' => $oid1->__toString(),
      'depth' => -1,
      'language' => 'de'
    );
    $response = $this->runRequest($data);

    // test
    $translatedObj = $response->getValue('node');
    $translatedChild = $translatedObj->getFirstChild();
    $this->assertTrue($translatedChild->getValue('sessionid') == 'Session Id [de]',
      "The translated value is 'Session Id [de]'");

    // cleanup
    TestUtil::deleteTestObject($oid1);
    Localization::deleteTranslation($oid1);
    TestUtil::deleteTestObject($oid2);
    Localization::deleteTranslation($oid2);
  }

  /**
   * @group controller
   */
  public function testDisplayTranslationOfReferencedValues()
  {
    $this->markTestIncomplete('This test is not ready to run yet.');

    // TODO: implement for input_type = select#async:ReferencedType, select#fkt:g_getOIDs|ReferencedType
  }
}
?>