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
 * $Id: InsertControllerTest.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once("lib/ControllerTestCase.php");

/**
 * @class InsertControllerTest
 * @ingroup test
 * @brief InsertControllerTest tests the InsertController.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InsertControllerTest extends ControllerTestCase
{
  const TEST_TYPE = 'UserRDB';
  const TEST_CHILD_TYPE = 'Locktable';
  const TEST_NM_CHILD_TYPE = 'RoleRDB';
  const TEST_OID1 = 'UserRDB:0';
  
  protected function getControllerName()
  {
    return 'InsertController';
  }

  public function testInsert()
  {
    // simulate a simple insert call with initial data
    $type = InsertControllerTest::TEST_TYPE;
    $testObj = new $type();
    $testObj->setValue('name', 'Administrator', DATATYPE_ATTRIBUTE);
    $data = array(
      'newtype' => InsertControllerTest::TEST_TYPE,
      InsertControllerTest::TEST_TYPE.':' => &$testObj
    );
    $response = $this->runRequest($data);
    
    // test
    $insertOID = $response->getValue('oid');
    $obj = &$this->loadTestObject($insertOID);
    $this->assertTrue($obj->getValue('name') == 'Administrator', "The name is 'Administrator'");

    // cleanup
    $this->deleteTestObject($insertOID);
  }

  public function testInsertWithChild()
  {
    $this->createTestObject(InsertControllerTest::TEST_OID1);

    // simulate an insert call with parent
    $data = array(
      'newtype' => InsertControllerTest::TEST_CHILD_TYPE,
      'poid' => InsertControllerTest::TEST_OID1
    );
    $response = $this->runRequest($data);
    
    // test
    $insertOID = $response->getValue('oid');
    $obj = &$this->loadTestObject($insertOID);
    $obj->loadParents(InsertControllerTest::TEST_TYPE);
    
    $this->assertTrue(sizeof($obj->getParentsEx(InsertControllerTest::TEST_OID1, null, null, null)) == 1, 
      InsertControllerTest::TEST_OID1." is a parent of the created child");
      
    // cleanup
    $this->deleteTestObject(InsertControllerTest::TEST_OID1);
    $this->deleteTestObject($insertOID);
  }

  public function testInsertWithManyToManyChild()
  {
    $this->createTestObject(InsertControllerTest::TEST_OID1);

    // simulate an insert call with parent
    $data = array(
      'newtype' => InsertControllerTest::TEST_NM_CHILD_TYPE,
      'poid' => InsertControllerTest::TEST_OID1
    );
    $response = $this->runRequest($data);
    
    // test
    $insertOID = $response->getValue('oid');
    $obj = &$this->loadTestObject($insertOID);
    $obj->loadChildren(InsertControllerTest::TEST_TYPE);
    
    $this->assertTrue(sizeof($obj->getChildrenEx(InsertControllerTest::TEST_OID1, null, null, null)) == 1, 
      InsertControllerTest::TEST_OID1." is a child of the created child");
      
    // cleanup
    $this->deleteTestObject(InsertControllerTest::TEST_OID1);
    $this->deleteTestObject($insertOID);
  }

  public function testInsertTranslation()
  {
    // simulate a translate call
    $type = InsertControllerTest::TEST_TYPE;
    $testObj = new $type();
    $testObj->setValue('name', 'Administrator [it]', DATATYPE_ATTRIBUTE);
    $data = array(
      'newtype' => InsertControllerTest::TEST_TYPE,
      InsertControllerTest::TEST_TYPE.':' => &$testObj,
      'language' => 'it'
    );
    $response = $this->runRequest($data);

    // test
    $insertOID = $response->getValue('oid');
    $translatedObj = &Localization::loadTranslatedObject($insertOID, 'it');
    $this->assertTrue($translatedObj->getValue('name') == 'Administrator [it]', 
      "The translated name is 'Administrator [it]'");

    // cleanup
    $this->deleteTestObject($insertOID);
    Localization::deleteTranslation($insertOID);
  }
}
?>