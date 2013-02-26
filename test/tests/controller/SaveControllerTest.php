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
use wcmf\lib\i18n\Localization;
use \wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

/**
 * SaveControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SaveControllerTest extends ControllerTestCase {

  const TEST_OID = 'UserRDB:0';

  protected function getControllerName() {
    return 'wcmf\application\controller\SaveController';
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
  public function testSave() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a simple save call
    $type = $oid->getType();
    $testObj = $persistenceFacade->create($type, BuildDepth::SINGLE);
    $testObj->setOID($oid);
    $testObj->setValue('name', 'Administrator');
    $data = array(
      $oid->__toString() => $testObj
    );
    $response = $this->runRequest('save', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $obj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $this->assertEquals('Administrator', $obj->getValue('name'));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testSaveTranslation() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(self::TEST_OID);

    // simulate a translate call
    $type = $oid->getType();
    $testObj = ObjectFactory::getInstance('persistenceFacade')->create($type, BuildDepth::SINGLE);
    $testObj->setOID($oid);
    $testObj->setValue('name', 'Administrator [it]');
    $data = array(
      $oid->__toString() => $testObj,
      'language' => 'it'
    );
    $response = $this->runRequest('save', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $translatedObj = Localization::getInstance()->loadTranslatedObject($oid, 'it');
    $this->assertEquals('Administrator [it]', $translatedObj->getValue('name'));

    TestUtil::endSession();
  }
}
?>