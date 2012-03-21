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
use wcmf\lib\persistence\ObjectId;

/**
 * SaveControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveControllerTest extends ControllerTestCase {

  const TEST_OID1 = 'UserRDB:0';

  protected function getControllerName() {
    return 'SaveController';
  }

  /**
   * @group controller
   */
  public function testSave() {
    $this->markTestIncomplete('This test is not ready to run yet.');

    $oid = ObjectId::parse(SaveControllerTest::TEST_OID1);
    TestUtil::createTestObject($oid);

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
    TestUtil::deleteTestObject($oid);
  }

  /**
   * @group controller
   */
  public function testSaveTranslation() {
    $this->markTestIncomplete('This test is not ready to run yet.');

    $oid = ObjectId::parse(SaveControllerTest::TEST_OID1);
    TestUtil::createTestObject($oid);

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
    TestUtil::deleteTestObject($oid);
    Localization::deleteTranslation($oid);
  }
}
?>