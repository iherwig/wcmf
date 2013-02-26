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

  const TEST_TYPE = 'UserRDB';
  const TEST_OID = 'UserRDB:1';

  protected function getControllerName() {
    return 'wcmf\application\controller\DeleteController';
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array('id' => 1),
      ),
      'user' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '21232f297a57a5a743894a0e4a801fc3'),
        array('id' => 1, 'login' => 'user1', 'name' => 'User 1', 'password' => '24c9e15e52afc47c225b757e7bee1f9d'),
      ),
      'translation' => array(
      ),
    ));
  }

  /**
   * @group controller
   */
  public function testDelete() {
    $sid = TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // simulate a delete call
    $data = array(
      'oid' => $oid
    );
    $response = $this->runRequest('delete', $data, $sid);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $oids = $persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE);
    $this->assertTrue(!in_array($oid, $oids), $oid." is does not exist after deleting");

    TestUtil::endSession($sid);
  }

  /**
   * @group controller
   */
  public function testDeleteTranslation() {
    $sid = TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // store a 1st translation
    $localization = Localization::getInstance();
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
    $response = $this->runRequest('delete', $data, $sid);

    // tests
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $oids = $persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE);
    $this->assertTrue(in_array($oid, $oids), $oid." still exists after deleting the translation");

    $query = new ObjectQuery(Localization::getTranslationType());
    $tpl = $query->getObjectTemplate(Localization::getTranslationType());
    $tpl->setObjectid($oid);
    $translations = $query->execute(BuildDepth::SINGLE);
    $this->assertTrue(sizeof($translations) > 0, "Not all translations are deleted");

    $translationsDe = Node::filter($translations, null, null, array('language' => 'de'), null, false);
    $this->assertEquals(0, sizeof($translationsDe), "All translations 'de' are deleted");

    TestUtil::endSession($sid);
  }

  /**
   * @group controller
   */
  public function testDeleteComplete() {
    $sid = TestUtil::startSession('admin', 'admin');
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // store a translation
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $testObj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
    $tmp = clone $testObj;
    $tmp->setValue('name', 'Herwig [de]');
    $tmp->setValue('firstname', 'Ingo [de]');
    Localization::getInstance()->saveTranslation($tmp, 'de');
    $transaction->commit();

    // simulate a delete call
    $data = array(
      'oid' => $oid->__toString()
    );
    $response = $this->runRequest('delete', $data, $sid);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $object = $persistenceFacade->create(self::TEST_TYPE);
    $object->setOID($oid);
    Localization::getInstance()->loadTranslation($object, 'de');
    $this->assertEquals(null, $object->getValue('name'));
    $this->assertEquals(null, $object->getValue('firstname'));

    TestUtil::endSession($sid);
  }
}
?>