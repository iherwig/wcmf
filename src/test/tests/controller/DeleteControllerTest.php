<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace test\tests\controller;

use test\lib\ArrayDataSet;
use test\lib\ControllerTestCase;
use test\lib\TestUtil;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

/**
 * DeleteControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DeleteControllerTest extends ControllerTestCase {

  const TEST_TYPE = 'User';
  const TEST_OID = 'User:1';

  protected function getControllerName() {
    return 'wcmf\application\controller\DeleteController';
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm'),
        array('id' => 1, 'login' => 'user1', 'name' => 'User 1', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02'),
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
      ),
      'Locktable' => array(
      ),
      'Translation' => array(
      ),
    ));
  }

  /**
   * @group controller
   */
  public function testDelete() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a delete call
    $data = array(
      'oid' => $oid
    );
    $response = $this->runRequest('delete', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $oids = $persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE);
    $this->assertTrue(!in_array($oid, $oids), $oid." is does not exist after deleting");

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDeleteTranslation() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // store a 1st translation
    $localization = ObjectFactory::getInstance('localization');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $testObj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $tmpDe = clone $testObj;
    $tmpDe->setValue('name', 'Herwig [de]');
    $tmpDe->setValue('firstname', 'Ingo [de]');
    $localization->saveTranslation($tmpDe, 'de');

    // store a 2nd translation
    $tmpIt = clone $testObj;
    $tmpIt->setValue('name', 'Herwig [it]');
    $tmpIt->setValue('firstname', 'Ingo [it]');
    $localization->saveTranslation($tmpIt, 'it');
    $transaction->commit();

    // simulate a delete call
    $data = array(
      'oid' => $oid->__toString(),
      'language' => 'de'
    );
    $response = $this->runRequest('delete', $data);

    // tests
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $oids = $persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE);
    $this->assertTrue(in_array($oid, $oids), $oid." still exists after deleting the translation");

    $query = new ObjectQuery('Translation', __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate('Translation');
    $tpl->setValue('objectid', $oid);
    $translations = $query->execute(BuildDepth::SINGLE);
    $this->assertTrue(sizeof($translations) > 0, "Not all translations are deleted");

    $translationsDe = Node::filter($translations, null, null, array('language' => 'de'), null, false);
    $this->assertEquals(0, sizeof($translationsDe), "All translations 'de' are deleted");

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDeleteComplete() {
    TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID);
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
    $data = array(
      'oid' => $oid->__toString()
    );
    $response = $this->runRequest('delete', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $object = $persistenceFacade->create(self::TEST_TYPE);
    $object->setOID($oid);
    ObjectFactory::getInstance('localization')->loadTranslation($object, 'de');
    $this->assertEquals(null, $object->getValue('name'));
    $this->assertEquals(null, $object->getValue('firstname'));

    TestUtil::endSession();
  }
}
?>