<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\controller;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * DeleteControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DeleteControllerTest extends ControllerTestCase {

  const TEST_TYPE1 = 'User';
  const TEST_TYPE2 = 'Book';
  const TEST_OID1 = 'User:1';
  const TEST_OID2 = 'Book:301';

  protected function getControllerName() {
    return 'wcmf\application\controller\DeleteController';
  }

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
        ['id' => 1, 'login' => 'user1', 'name' => 'User 1', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02', 'active' => 1, 'super_user' => 0, 'config' => ''],
      ],
      'NMUserRole' => [
        ['fk_user_id' => 0, 'fk_role_id' => 0],
      ],
      'Role' => [
        ['id' => 0, 'name' => 'administrators'],
      ],
      'Book' => [
        ['id' => 301, 'title' => 'title [en]', 'description' => 'description [en]', 'year' => ''],
      ],
      'Lock' => [
      ],
      'Translation' => [
      ],
    ]);
  }

  /**
   * @group controller
   */
  public function testDelete() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID1);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a delete call
    $data = [
      'oid' => $oid
    ];
    $response = $this->runRequest('delete', $data);

    // test
    $oids = $persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE1);
    $this->assertTrue(!in_array($oid, $oids), $oid." is does not exist after deleting");

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDeleteTranslation() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID2);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // store a 1st translation
    $localization = ObjectFactory::getInstance('localization');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $testObj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $tmpDe = clone $testObj;
    $tmpDe->setValue('title', 'title [de]');
    $tmpDe->setValue('description', 'description [de]');
    $localization->saveTranslation($tmpDe, 'de');

    // store a 2nd translation
    $tmpIt = clone $testObj;
    $tmpIt->setValue('title', 'title [it]');
    $tmpIt->setValue('description', 'description [it]');
    $localization->saveTranslation($tmpIt, 'it');
    $transaction->commit();

    // simulate a delete call
    $data = [
      'oid' => $oid->__toString(),
      'language' => 'de'
    ];
    $response = $this->runRequest('delete', $data);

    // tests
    $oids = $persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE2);
    $this->assertTrue(in_array($oid, $oids), $oid." still exists after deleting the translation");

    $query = new ObjectQuery('Translation', __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate('Translation');
    $tpl->setValue('objectid', $oid);
    $translations = $query->execute(BuildDepth::SINGLE);
    $this->assertTrue(sizeof($translations) > 0, "Not all translations are deleted");

    $translationsDe = Node::filter($translations, null, null, ['language' => 'de'], null, false);
    $this->assertEquals(0, sizeof($translationsDe), "All translations 'de' are deleted");

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDeleteComplete() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID1);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // store a translation
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $testObj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [de]');
    $tmp->setValue('firstname', 'Ingo [de]');
    ObjectFactory::getInstance('localization')->saveTranslation($tmp, 'de');
    $transaction->commit();

    // simulate a delete call
    $data = [
      'oid' => $oid->__toString()
    ];
    $response = $this->runRequest('delete', $data);

    // test
    $object = $persistenceFacade->create(self::TEST_TYPE1);
    $object->setOID($oid);
    $object = ObjectFactory::getInstance('localization')->loadTranslation($object, 'de');
    $this->assertEquals(null, $object->getValue('name'));
    $this->assertEquals(null, $object->getValue('firstname'));

    TestUtil::endSession();
  }
}
?>