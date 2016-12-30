<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * TransactionTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TransactionTest extends DatabaseTestCase {

  private $publisherOidStr = 'Publisher:12345';
  private $authorOidStr = 'Author:12345';

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''),
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
      ),
      'Publisher' => array(
        array('id' => 12345),
      ),
      'NMPublisherAuthor' => array(
        array('id' => 123451, 'fk_publisher_id' => 12345, 'fk_author_id' => 12345),
      ),
      'Author' => array(
        array('id' => 12345),
      ),
    ));
  }

  public function testSimple() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // create a new object
    $newPublisher1 = $persistenceFacade->create('Publisher');
    $newName = time();
    $newPublisher1->setValue('name', $newName);
    $id1 = $newPublisher1->getOID()->getFirstId();
    $this->assertTrue(ObjectId::isDummyId($id1));
    // update an existing object
    $existingPublisher1 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr));
    $modifiedName = $existingPublisher1->getValue('name')." modified";
    $existingPublisher1->setValue('name', $modifiedName);
    $this->assertEquals($modifiedName, $existingPublisher1->getValue('name'));
    // delete an existing object
    $author1 = $persistenceFacade->load(ObjectId::parse($this->authorOidStr));
    $author1->delete();
    $transaction->commit();

    // the new object has a valid oid assigned by the persistence layer
    $id2 = $newPublisher1->getOID()->getFirstId();
    $this->assertFalse(ObjectId::isDummyId($id2));

    // load the objects again
    $newPublisher2 = $persistenceFacade->load($newPublisher1->getOID());
    $this->assertEquals($newName, $newPublisher2->getValue('name'));
    $existingPublisher2 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr));
    $this->assertEquals($modifiedName, $existingPublisher2->getValue('name'));
    $author2 = $persistenceFacade->load(ObjectId::parse($this->authorOidStr));
    $this->assertNull($author2);

    TestUtil::endSession();
  }

  public function testChangesOutOfTxBoundaries() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    // load the object inside the transaction
    $transaction->begin();
    $chapter1 = $persistenceFacade->loadFirstObject('Publisher');
    $oldName = $chapter1->getValue('name');
    $transaction->rollback();

    // update the object in another transaction (should also change the object)
    $transaction->begin();
    $modifiedName = $oldName." modified";
    $chapter1->setValue('name', $modifiedName);
    $this->assertEquals($modifiedName, $chapter1->getValue('name'));
    $transaction->commit();

    // load the object
    $chapter2 = $persistenceFacade->load($chapter1->getOID());
    $this->assertEquals($modifiedName, $chapter2->getValue('name'));

    TestUtil::endSession();
  }

  public function testRollback() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // update an object
    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr));
    $oldName = $chapter1->getValue('name');
    $chapter1->setValue('name', $oldName." modified");
    // delete an object
    $author1 = $persistenceFacade->load(ObjectId::parse($this->authorOidStr));
    $author1->delete();
    $transaction->rollback();

    // load the objects again
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr));
    $this->assertEquals($oldName, $chapter2->getValue('name'));
    $author2 = $persistenceFacade->load(ObjectId::parse($this->authorOidStr));
    $this->assertNotNull($author2);

    TestUtil::endSession();
  }

  public function testSingleInstancePerEntity() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // update objects
    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr), 1);
    $modifiedName = $chapter1->getValue('name')." modified";
    $chapter1->setValue('name', $modifiedName);
    $this->assertEquals($modifiedName, $chapter1->getValue('name'));
    $author1 = $chapter1->getFirstChild('Author');
    $modifiedName = $author1->getValue('name')." modified";
    $author1->setValue('name', $modifiedName);
    $this->assertEquals($modifiedName, $author1->getValue('name'));
    // reload objects
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr), 1);
    $this->assertEquals($modifiedName, $chapter2->getValue('name'));
    $author2 = $chapter2->getFirstChild('Author');
    $this->assertEquals($modifiedName, $author2->getValue('name'));
    $author3 = $persistenceFacade->load(ObjectId::parse($this->authorOidStr), 1);
    $this->assertEquals($modifiedName, $author3->getValue('name'));
    $publisher3 = $author3->getFirstChild('Publisher');
    $this->assertEquals($modifiedName, $publisher3->getValue('name'));
    $transaction->rollback();

    TestUtil::endSession();
  }
}
?>