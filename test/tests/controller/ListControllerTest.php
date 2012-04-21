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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Localization;
use wcmf\lib\model\Node;
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

  protected function setUp() {
    parent::setUp();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    TestUtil::createTestObject(ObjectId::parse(self::TEST_OID), array('login' => 'test'));
    $transaction->commit();
  }

  protected function tearDown() {
    $localization = Localization::getInstance();
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse(self::TEST_OID));
    $localization->deleteTranslation(ObjectId::parse(self::TEST_OID));
    $transaction->commit();
    parent::tearDown();
  }

  /**
   * @group controller
   */
  public function testSimpleList() {
    $oid = ObjectId::parse(self::TEST_OID);

    // simulate a simple list call
    $type = $oid->getType();
    $data = array(
      'className' => $type
    );
    $response = $this->runRequest($data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $objects = $response->getValue('list');
    $filteredObjects = Node::filter($objects, $oid, null, null, null);
    $this->assertEquals(1, sizeof($filteredObjects));
    // can only ask for display values
    $this->assertEquals('test', $filteredObjects[0]->getValue('login'));
  }

  /**
   * @group controller
   */
  public function _testDisplayTranslation() {
    $oid = ObjectId::parse(self::TEST_OID);
    $testObj = TestUtil::loadTestObject($oid);

    // store a translation
    $tmp = clone $testObj;
    $tmp->setValue('login', 'test [de]');
    Localization::saveTranslation($tmp, 'de');
    ObjectFactory::getInstance('persistenceFacade')->getTransaction()->commit();

    // simulate a localized display call
    $type = $oid->getType();
    $data = array(
      'className' => $type,
      'language' => 'de'
    );
    $response = $this->runRequest($data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $objects = $response->getValue('objects');
    $filteredObjects = Node::filter($objects, $oid, null, null, null);
    $this->assertEquals(1, sizeof($filteredObjects));
    // can only ask for display values
    $this->assertEquals('test [de]', $filteredObjects[0]->getValue('login'));
  }
}
?>