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
 * $Id: SaveControllerTest.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once("lib/ControllerTestCase.php");

/**
 * @class SaveControllerTest
 * @ingroup test
 * @brief SaveControllerTest tests the SaveController.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveControllerTest extends ControllerTestCase
{
  const TEST_OID1 = 'UserRDB:0';

  protected function getControllerName()
  {
    return 'SaveController';
  }

  public function testSave()
  {
    $oid = ObjectId::parse(SaveControllerTest::TEST_OID1);
    $this->createTestObject($oid);

    // simulate a simple save call
    $type = $oid->getType();
    $testObj = new $type();
    $testObj->setValue('name', 'Administrator');
    $data = array(
      $oid->__toString() => &$testObj
    );
    $this->runRequest($data);

    // test
    $obj = $this->loadTestObject($oid);
    $this->assertTrue($obj->getValue('name') == 'Administrator', "The name is 'Administrator'");

    // cleanup
    $this->deleteTestObject($oid);
  }

  public function testSaveTranslation()
  {
    $oid = ObjectId::parse(SaveControllerTest::TEST_OID1);
    $this->createTestObject($oid);

    // simulate a translate call
    $type = $oid->getType();
    $testObj = new $type();
    $testObj->setValue('name', 'Administrator [it]');
    $data = array(
      $oid->__toString() => &$testObj,
      'language' => 'it'
    );
    $this->runRequest($data);

    // test
    $translatedObj = Localization::loadTranslatedObject($oid, 'it');
    $this->assertTrue($translatedObj->getValue('name') == 'Administrator [it]',
      "The translated name is 'Administrator [it]'");

    // cleanup
    $this->deleteTestObject($oid);
    Localization::deleteTranslation($oid);
  }
}
?>