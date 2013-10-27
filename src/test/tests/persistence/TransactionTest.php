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
namespace test\tests\persistence;

use test\lib\ArrayDataSet;
use test\lib\DatabaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;

/**
 * TransactionTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TransactionTest extends DatabaseTestCase {

  private $_publisherOidStr = 'Publisher:12345';
  private $_authorOidStr = 'Author:12345';

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array('id' => 1),
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
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // create a new object
    $newPublisher1 = $persistenceFacade->create('Publisher');
    $newName = time();
    $newPublisher1->setName($newName);
    $id1 = $newPublisher1->getOID()->getFirstId();
    $this->assertTrue(ObjectId::isDummyId($id1));
    // modify an existing object
    $existingPublisher1 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr));
    $modifiedName = $existingPublisher1->getName()." modified";
    $existingPublisher1->setName($modifiedName);
    $this->assertEquals($modifiedName, $existingPublisher1->getName());
    // delete an existing object
    $author1 = $persistenceFacade->load(ObjectId::parse($this->_authorOidStr));
    $author1->delete();
    $transaction->commit();

    // the new object has a valid oid assigned by the persistence layer
    $id2 = $newPublisher1->getOID()->getFirstId();
    $this->assertFalse(ObjectId::isDummyId($id2));

    // load the objects again
    $newPublisher2 = $persistenceFacade->load($newPublisher1->getOID());
    $this->assertEquals($newName, $newPublisher2->getName());
    $existingPublisher2 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr));
    $this->assertEquals($modifiedName, $existingPublisher2->getName());
    $author2 = $persistenceFacade->load(ObjectId::parse($this->_authorOidStr));
    $this->assertNull($author2);

    TestUtil::runAnonymous(false);
  }

  public function testChangesOutOfTxBoundaries() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    // load the object inside the transaction
    $transaction->begin();
    $chapter1 = $persistenceFacade->loadFirstObject('Publisher');
    $oldName = $chapter1->getName();
    $transaction->rollback();

    // modify the object in another transaction (should also
    // change the object)
    $transaction->begin();
    $modifiedName = $oldName." modified";
    $chapter1->setName($modifiedName);
    $this->assertEquals($modifiedName, $chapter1->getName());
    $transaction->commit();

    // load the object
    $chapter2 = $persistenceFacade->load($chapter1->getOID());
    $this->assertEquals($modifiedName, $chapter2->getName());

    TestUtil::runAnonymous(false);
  }

  public function testRollback() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // modify an object
    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr));
    $oldName = $chapter1->getName();
    $chapter1->setName($oldName." modified");
    // delete an object
    $author1 = $persistenceFacade->load(ObjectId::parse($this->_authorOidStr));
    $author1->delete();
    $transaction->rollback();

    // load the objects again
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr));
    $this->assertEquals($oldName, $chapter2->getName());
    $author2 = $persistenceFacade->load(ObjectId::parse($this->_authorOidStr));
    $this->assertNotNull($author2);

    TestUtil::runAnonymous(false);
  }

  public function testSingleInstancePerEntity() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // modify objects
    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr), 1);
    $modifiedName = $chapter1->getName()." modified";
    $chapter1->setName($modifiedName);
    $this->assertEquals($modifiedName, $chapter1->getName());
    $author1 = $chapter1->getFirstChild('Author');
    $modifiedName = $author1->getName()." modified";
    $author1->setName($modifiedName);
    $this->assertEquals($modifiedName, $author1->getName());
    // reload objects
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr), 1);
    $this->assertEquals($modifiedName, $chapter2->getName());
    $author2 = $chapter2->getFirstChild('Author');
    $this->assertEquals($modifiedName, $author2->getName());
    $author3 = $persistenceFacade->load(ObjectId::parse($this->_authorOidStr), 1);
    $this->assertEquals($modifiedName, $author3->getName());
    $publisher3 = $author3->getFirstChild('Publisher');
    $this->assertEquals($modifiedName, $publisher3->getName());
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }
}
?>