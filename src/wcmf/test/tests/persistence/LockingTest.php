<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
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
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\Lock;
use wcmf\lib\persistence\concurrency\OptimisticLockException;
use wcmf\lib\persistence\concurrency\PessimisticLockException;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * LockingTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LockingTest extends DatabaseTestCase {

  private $bookOidStr = 'Book:777';

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
      ),
      'User' => array(
        array('id' => 555, 'login' => 'user1', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02', 'active' => 1, 'super_user' => 0, 'config' => ''),
        array('id' => 666, 'login' => 'user2', 'password' => '$2y$10$.q/JnbXAWDI8pZUqZmjON.YbZsSeQCLgh3aKMYC/Nmsx5VMRti8v.', 'active' => 1, 'super_user' => 0, 'config' => ''),
      ),
      'Book' => array(
        array('id' => 777),
      ),
      'Lock' => array(
      ),
    ));
  }

  public function testPessimisticLock() {
    $oid = ObjectId::parse($this->bookOidStr);

    // lock
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, 'user1'));
    ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, 'user1'));
    TestUtil::endSession();

    // expect lock not to be removed
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, 'user1'));

    // release
    $sid2 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, 'user1'));
    ObjectFactory::getInstance('concurrencyManager')->releaseLock($oid);
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, 'user1'));
    TestUtil::endSession();

    // expect lock not to be removed
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, 'user1'));
  }

  public function testPessimisticConcurrentLock() {
    $oid = ObjectId::parse($this->bookOidStr);

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, 'user1'));
    ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, 'user1'));
    TestUtil::endSession();

    // user 2 tries to lock the object
    $sid2 = TestUtil::startSession('user2', 'user2');
    try {
      ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    }
    catch (PessimisticLockException $ex) {
      // check if no lock was aquired
      $this->assertEquals(0, $this->getNumPessimisticLocks($oid, 'user2'));
      TestUtil::endSession();
      return;
    }
    $this->fail('Expected PessimisticLockException has not been raised.');
  }

  public function testPessimisticConcurrentUpdate() {
    $oid = ObjectId::parse($this->bookOidStr);

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, 'user1'));
    ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, 'user1'));
    TestUtil::endSession();

    // user 2 tries to update the object
    $sid2 = TestUtil::startSession('user2', 'user2');
    $objectTitle = '';
    try {
      $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
      $transaction->begin();
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $objectTitle = $object->getValue('title');
      $object->setValue('title', $objectTitle.'modified');
      $transaction->commit();
    }
    catch (PessimisticLockException $ex) {
      // check if the object is not modified
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $this->assertEquals($objectTitle, $object->getValue('title'));
      TestUtil::endSession();
      return;
    }
    $this->fail('Expected PessimisticLockException has not been raised.');
  }

  public function testPessimisticConcurrentDelete() {
    $oid = ObjectId::parse($this->bookOidStr);

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $this->assertEquals(0, $this->getNumPessimisticLocks($oid, 'user1'));
    ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_PESSIMISTIC);
    $this->assertEquals(1, $this->getNumPessimisticLocks($oid, 'user1'));
    TestUtil::endSession();

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
      TestUtil::endSession();
      return;
    }
    $this->fail('Expected PessimisticLockException has not been raised.');
  }

  public function testOptimisticLock() {
    $oid = ObjectId::parse($this->bookOidStr);

    // user 1 locks the object and modifies it
    $sid1 = TestUtil::startSession('user1', 'user1');
    $transaction1 = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction1->begin();
    $object1 = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_OPTIMISTIC, $object1);
    $newTitle = time();
    $object1->setValue('title', $newTitle);
    $transaction1->commit();

    // check if the object was updated
    $transaction2 = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction2->begin();
    $object2 = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    $this->assertEquals($newTitle, $object2->getValue('title'));
    $transaction2->rollback();
    TestUtil::endSession();
  }

  public function testOptimisticConcurrentUpdate() {
    $oid = ObjectId::parse($this->bookOidStr);

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_OPTIMISTIC, $object);
    $originalTitle = $object->getValue('title');
    $object->setValue('title', $originalTitle.'modified');

    // simulate update by user 2
    $newTitle = time();
    $mapper = $object->getMapper();
    $mapper->executeSql("UPDATE Book SET title='".$newTitle."' WHERE id=777");

    try {
      // user 1 tries to commit
      $transaction->commit();
    }
    catch (OptimisticLockException $ex) {
      // check if the object still has the value set by user 2
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $this->assertEquals($newTitle, $object->getValue('title'));
      TestUtil::endSession();
      return;
    }
    $this->fail('Expected OptimisticLockException has not been raised.');
  }

  public function testOptimisticConcurrentDelete() {
    $oid = ObjectId::parse($this->bookOidStr);

    // user 1 locks the object
    $sid1 = TestUtil::startSession('user1', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
    ObjectFactory::getInstance('concurrencyManager')->aquireLock($oid, Lock::TYPE_OPTIMISTIC, $object);
    $originalTitle = $object->getValue('title');
    $object->setValue('title', $originalTitle.'modified');

    // simulate delete by user 2
    $mapper = $object->getMapper();
    $mapper->executeSql("DELETE FROM Book WHERE id=777");

    try {
      // user 1 tries to commit
      $transaction->commit();
    }
    catch (OptimisticLockException $ex) {
      // check if the object is still deleted
      $object = ObjectFactory::getInstance('persistenceFacade')->load($oid, BuildDepth::SINGLE);
      $this->assertNull($object);
      TestUtil::endSession();
      return;
    }
    $this->fail('Expected OptimisticLockException has not been raised.');
  }

  protected function getNumPessimisticLocks($oid, $login) {
    return $this->getConnection()->getRowCount('Lock', "objectid = '".
            $oid."' AND login = '".$login."'");
  }
}
?>