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
 * $Id: DeleteControllerTest.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once("lib/ControllerTestCase.php");

/**
 * @class DeleteControllerTest
 * @ingroup test
 * @brief DeleteControllerTest tests the DeleteController.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DeleteControllerTest extends ControllerTestCase
{
  const TEST_TYPE = 'UserRDB';
  const TEST_OID1 = 'UserRDB:0';

  protected function getControllerName()
  {
    return 'DeleteController';
  }

  public function testDelete()
  {
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID1);
    $this->createTestObject($oid);

    // simulate a delete call
    $data = array(
      'deleteoids' => $oid
    );
    $response = $this->runRequest($data);

    // test
    $persistenceFacade = PersistenceFacade::getInstance();
    $oids = $persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE);

    $this->assertTrue(!in_array($oid, $oids), $oid." is does not exist after deleting");
  }

  public function testDeleteTranslation()
  {
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID1);
    $testObj = $this->createTestObject($oid);

    // store a 1st translation
    $tmpDe = $testObj->duplicate();
    $tmpDe->setValue('name', 'Herwig [de]');
    $tmpDe->setValue('firstname', 'Ingo [de]');
    Localization::saveTranslation($tmpDe, 'de');

    // store a 2nd translation
    $tmpIt = $testObj->duplicate();
    $tmpIt->setValue('name', 'Herwig [it]');
    $tmpIt->setValue('firstname', 'Ingo [it]');
    Localization::saveTranslation($tmpIt, 'it');

    // simulate a delete call
    $data = array(
      'deleteoids' => $oid->__toString(),
      'language' => 'de'
    );
    $response = $this->runRequest($data);

    // tests
    $persistenceFacade = PersistenceFacade::getInstance();
    $oids = &$persistenceFacade->getOIDs(DeleteControllerTest::TEST_TYPE);
    $this->assertTrue(in_array($oid, $oids), $oid." still exists after deleting the translation");

    $query = $persistenceFacade->createObjectQuery(Localization::getTranslationType());
    $tpl = $query->getObjectTemplate(Localization::getTranslationType());
    $tpl->setObjectid($oid);
    $translations = $query->execute(BUIDLDEPTH_SINGLE);
    $this->assertTrue(sizeof($translations) > 0, "Not all translations are deleted");

    $translationsDe = Node::filter($translations, null, null, array('language' => 'de'), null, false);
    $this->assertTrue(sizeof($translationsDe) == 0, "All translations 'de' are deleted");

    // cleanup
    $this->deleteTestObject($oid);
    Localization::deleteTranslation($oid);
  }

  public function testDeleteComplete()
  {
    $oid = ObjectId::parse(DeleteControllerTest::TEST_OID1);
    $testObj = $this->createTestObject($oid);

    // store a translation
    $tmp = $testObj->duplicate();
    $tmp->setValue('name', 'Herwig [de]');
    $tmp->setValue('firstname', 'Ingo [de]');
    Localization::saveTranslation($tmp, 'de');

    // simulate a delete call
    $data = array(
      'deleteoids' => $oid->__toString()
    );
    $response = $this->runRequest($data);

    // test
    $query = PersistenceFacade::getInstance()->createObjectQuery(Localization::getTranslationType());
    $tpl = $query->getObjectTemplate(Localization::getTranslationType());
    $tpl->setObjectid($oid);
    $oids = $query->execute(false);
    $this->assertTrue(sizeof($oids) == 0, "All translations are deleted");
  }
}
?>