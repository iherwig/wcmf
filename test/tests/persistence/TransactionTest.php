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

use test\lib\TestUtil;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * TransactionTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TransactionTest extends \PHPUnit_Framework_TestCase {

  private $_pageOidStr = 'Page:12345';
  private $_documentOidStr = 'Document:12345';

  protected function setUp() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page = TestUtil::createTestObject(ObjectId::parse($this->_pageOidStr), array());
    $document = TestUtil::createTestObject(ObjectId::parse($this->_documentOidStr), array());
    $page->addNode($document);
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown() {
    TestUtil::runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_pageOidStr));
    TestUtil::deleteTestObject(ObjectId::parse($this->_documentOidStr));
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testSimple() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // create a new object
    $newPage = $persistenceFacade->create('Page');
    $newName = time();
    $newPage->setName($newName);
    $id = $newPage->getOID()->getFirstId();
    $this->assertTrue(ObjectId::isDummyId($id));
    // modfy an existing object
    $existingPage = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr));
    $modifiedName = $existingPage->getName()." modified";
    $existingPage->setName($modifiedName);
    $this->assertEquals($modifiedName, $existingPage->getName());
    // delete an existing object
    $document = $persistenceFacade->load(ObjectId::parse($this->_documentOidStr));
    $document->delete();
    $transaction->commit();

    // the new object has a valid oid assigned by the persistence layer
    $id = $newPage->getOID()->getFirstId();
    $this->assertFalse(ObjectId::isDummyId($id));

    // load the objects again
    $newPage2 = $persistenceFacade->load($newPage->getOID());
    $this->assertEquals($newName, $newPage2->getName());
    $existingPage2 = $persistenceFacade->load($existingPage->getOID());
    $this->assertEquals($modifiedName, $existingPage2->getName());
    $document2 = $persistenceFacade->load($document->getOID());
    $this->assertNull($document2);

    TestUtil::runAnonymous(false);
  }

  public function testChangesOutOfTxBoundaries() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();

    // load the object inside the transaction
    $transaction->begin();
    $page = $persistenceFacade->loadFirstObject('Page');
    $oldName = $page->getName();
    $transaction->rollback();

    // modify the object in another transaction (should not
    // change the object, since it is not attached to that transaction)
    $transaction->begin();
    $page->setName($oldName." modified");
    $this->assertEquals($oldName." modified", $page->getName());
    $transaction->commit();

    // load the object
    $page2 = $persistenceFacade->load($page->getOID());
    $this->assertEquals($oldName, $page2->getName());

    TestUtil::runAnonymous(false);
  }

  public function testRollback() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // modify an object
    $page = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr));
    $oldName = $page->getName();
    $page->setName($oldName." modified");
    // delete an object
    $document = $persistenceFacade->load(ObjectId::parse($this->_documentOidStr));
    $document->delete();
    $transaction->rollback();

    // load the objects again
    $page2 = $persistenceFacade->load($page->getOID());
    $this->assertEquals($oldName, $page2->getName());
    $document2 = $persistenceFacade->load($document->getOID());
    $this->assertNotNull($document2);

    TestUtil::runAnonymous(false);
  }

  public function testSingleInstancePerEntity() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    // modify objects
    $page1 = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), 1);
    $modifiedName = $page1->getName()." modified";
    $page1->setName($modifiedName);
    $this->assertEquals($modifiedName, $page1->getName());
    $document1 = $page1->getFirstChild('Document');
    $modifiedTitle = $document1->getTitle()." modified";
    $document1->setTitle($modifiedTitle);
    $this->assertEquals($modifiedTitle, $document1->getTitle());
    // reload objects
    $page2 = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), 1);
    $this->assertEquals($modifiedName, $page2->getName());
    $document2 = $page2->getFirstChild('Document');
    $this->assertEquals($modifiedTitle, $document2->getTitle());
    $document3 = $persistenceFacade->load(ObjectId::parse($this->_documentOidStr), 1);
    $this->assertEquals($modifiedTitle, $document3->getTitle());
    $page3 = $document3->getFirstChild('Page');
    $this->assertEquals($modifiedName, $page3->getName());
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }
}
?>