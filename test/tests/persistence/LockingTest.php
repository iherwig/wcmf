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
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\concurrency\Lock;
use wcmf\lib\persistence\concurrency\OptimisticLockException;
use wcmf\lib\persistence\concurrency\PessimisticLockException;

/**
 * LockingTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LockingTest extends \PHPUnit_Framework_TestCase {

  private $_user1OidStr = 'UserRDB:555';
  private $_user2OidStr = 'UserRDB:666';
  private $_user3OidStr = 'UserRDB:777';

  protected function setUp() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    TestUtil::createTestObject(ObjectId::parse($this->_user1OidStr), array(
        'login' => 'user1', 'password' => '24c9e15e52afc47c225b757e7bee1f9d'));
    TestUtil::createTestObject(ObjectId::parse($this->_user2OidStr), array(
        'login' => 'user2', 'password' => '7e58d63b60197ceb55a1c487989a3720'));
    TestUtil::createTestObject(ObjectId::parse($this->_user3OidStr), array());
    ObjectFactory::getInstance('concurrencymanager')->releaseLocks(ObjectId::parse($this->_user3OidStr));
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown() {
    TestUtil::runAnonymous(true);
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    ObjectFactory::getInstance('concurrencymanager')->releaseLocks(ObjectId::parse($this->_user3OidStr));
    TestUtil::deleteTestObject(ObjectId::parse($this->_user1OidStr));
    TestUtil::deleteTestObject(ObjectId::parse($this->_user2OidStr));
    TestUtil::deleteTestObject(ObjectId::parse($this->_user3OidStr));
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testPessimisticLock() {
    $oid = ObjectId::parse($this->_user3OidStr);
    $user1Id = ObjectId::parse($this->_user1OidStr)->getFirstId();

    // lock
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, $user1Id));
    ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, $user1Id));
    TestUtil::endSession($sid1);

    // expect lock not to be removed
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, $user1Id));

    // release
    $sid2 = TestUtil::startSession('user1', 'user1');
    $this->assertNotEquals($sid1, $sid2);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, $user1Id));
    ObjectFactory::getInstance('concurrencymanager')->releaseLock($oid);
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, $user1Id));
    TestUtil::endSession($sid2);

    // expect lock not to be removed
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, $user1Id));
  }

  public function testPessimisticConcurrentLock() {
    $oid = ObjectId::parse($this->_user3OidStr);
    $user1Id = ObjectId::parse($this->_user1OidStr)->getFirstId();
    $user2Id = ObjectId::parse($this->_user2OidStr)->getFirstId();

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, $user1Id));
    ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, $user1Id));
    TestUtil::endSession($sid1);

    // user 2 tries to lock the object
    $sid2 = TestUtil::startSession('user2', 'user2');
    try {
      ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    }
    catch (PessimisticLockException $ex) {
      // check if no lock was aquired
      $this->assertEquals(0, $this->getNumPessimisticLocks($oid, $user2Id));
      TestUtil::endSession($sid2);
      return;
    }
    $this->fail('Expected PessimisticLockException has not been raised.');
  }

  public function testPessimisticConcurrentUpdate() {
    $oid = ObjectId::parse($this->_user3OidStr);
    $user1Id = ObjectId::parse($this->_user1OidStr)->getFirstId();
    $user2Id = ObjectId::parse($this->_user2OidStr)->getFirstId();

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, $user1Id));
    ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, $user1Id));
    TestUtil::endSession($sid1);

    // user 2 tries to update the object
    $sid2 = TestUtil::startSession('user2', 'user2');
    $objectName = '';
    try {
      $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
      $transaction->begin();
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $objectName = $object->getName();
      $object->setValue('name', $objectName.'modified');
      $transaction->commit();
    }
    catch (PessimisticLockException $ex) {
      // check if the object is not modified
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $this->assertEquals($objectName, $object->getName());
      TestUtil::endSession($sid2);
      return;
    }
    $this->fail('Expected PessimisticLockException has not been raised.');
  }

  public function testPessimisticConcurrentDelete() {
    $oid = ObjectId::parse($this->_user3OidStr);
    $user1Id = ObjectId::parse($this->_user1OidStr)->getFirstId();
    $user2Id = ObjectId::parse($this->_user2OidStr)->getFirstId();

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, $user1Id));
    ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, $user1Id));
    TestUtil::endSession($sid1);

    // user 2 tries to delete the object
    $sid2 = TestUtil::startSession('user2', 'user2');
    try {
      $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
      $transaction->begin();
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $object->delete();
      $transaction->commit();
    }
    catch (PessimisticLockException $ex) {
      // check if the object is not deleted
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $this->assertNotEquals(null, $object);
      TestUtil::endSession($sid2);
      return;
    }
    $this->fail('Expected PessimisticLockException has not been raised.');
  }

  public function testOptimisticLock() {
    $oid = ObjectId::parse($this->_user3OidStr);

    // user 1 locks the object and modifies it
    $sid1 = TestUtil::startSession('user1', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_OPTIMISTIC, $object);
    $newFirstname = time();
    $object->setFirstname($newFirstname);
    $transaction->commit();

    // check if the object was updated
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    $this->assertEquals($newFirstname, $object->getFirstname());
    $transaction->rollback();
    TestUtil::endSession($sid1);
  }

  public function testOptimisticConcurrentUpdate() {
    $oid = ObjectId::parse($this->_user3OidStr);

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_OPTIMISTIC, $object);
    $originalFirstname = $object->getFirstname();
    $object->setFirstname($originalFirstname.'modified');

    // simulate update by user 2
    $newFirstname = time();
    $connection = $object->getMapper()->getConnection();
    $connection->exec("UPDATE user SET firstname='".$newFirstname."' WHERE id=777");

    try {
      // user 1 tries to commit
      $transaction->commit();
    }
    catch (OptimisticLockException $ex) {
      // check if the object still has the value set by user 2
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $this->assertEquals($newFirstname, $object->getFirstname());
      TestUtil::endSession($sid1);
      return;
    }
    $this->fail('Expected OptimisticLockException has not been raised.');
  }

  public function testOptimisticConcurrentDelete() {
    $oid = ObjectId::parse($this->_user3OidStr);

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    ObjectFactory::getInstance('concurrencymanager')->aquireLock($oid, Lock::TYPE_OPTIMISTIC, $object);
    $originalFirstname = $object->getFirstname();
    $object->setFirstname($originalFirstname.'modified');

    // simulate delete by user 2
    $connection = $object->getMapper()->getConnection();
    $connection->exec("DELETE FROM user WHERE id=777");

    try {
      // user 1 tries to commit
      $transaction->commit();
    }
    catch (OptimisticLockException $ex) {
      // check if the object is still deleted
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $this->assertNull($object);
      TestUtil::endSession($sid1);
      return;
    }
    $this->fail('Expected OptimisticLockException has not been raised.');
  }

  protected function getNumPessimisticLocks($oid, $userId) {
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    $tpl2 = $query->getObjectTemplate('UserRDB');
    $tpl2->setValue('id', Criteria::asValue("=", $userId));
    $tpl2->addNode($tpl);
    return sizeof($query->execute(false));
  }
}
?>