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

  public function testSimpleDisplay()
  {
    $oid = ObjectId::parse(DisplayControllerTest::TEST_OID1);
    $this->createTestObject($oid, array('name' => 'Administrator'));

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
    $this->deleteTestObject($oid);
  }

  public function testDisplayTranslation()
  {
    $oid = ObjectId::parse(DisplayControllerTest::TEST_OID1);
    $testObj = $this->createTestObject($oid, array('name' => 'Administrator'));

    // store a translation
    $tmp = $testObj->duplicate();
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
    $this->deleteTestObject($oid);
    Localization::deleteTranslation($oid);
  }

  public function testDisplayTranslationOfReferencedObjects()
  {
    $oid1 = ObjectId::parse(DisplayControllerTest::TEST_OID1);
    $oid2 = ObjectId::parse(DisplayControllerTest::TEST_OID2);
    $testObj1 = &$this->createTestObject($oid1, array('name' => 'Administrator'));
    $testObj2 = &$this->createTestObject($oid2, array('sessionid' => 'Session Id'));
    $testObj1->addChild($testObj2);
    $testObj2->save();

    // store a translations
    $tmp = $testObj1->duplicate();
    $tmp->setValue('name', 'Administrator [de]');
    Localization::saveTranslation($tmp, 'de');
    $tmp = $testObj2->duplicate();
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
    $this->deleteTestObject($oid1);
    Localization::deleteTranslation($oid1);
    $this->deleteTestObject($oid2);
    Localization::deleteTranslation($oid2);
  }

  public function testDisplayTranslationOfReferencedValues()
  {
    // TODO: implement for input_type = select#async:ReferencedType, select#fkt:g_getOIDs|ReferencedType
  }
}
?>