<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\security;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;
use wcmf\test\lib\TestUtil;

/**
 * PermissionsTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionsTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'User' => array(
        array('id' => 555, 'login' => 'userPermTest', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02', 'config' => 'permissions.ini')
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 555, 'fk_role_id' => 555),
      ),
      'Role' => array(
        array('id' => 555, 'name' => 'tester'),
      ),
      'Author' => array(
        array('id' => 111, 'name' => 'Author1'),
        array('id' => 222, 'name' => 'Author2'),
      ),
      'Publisher' => array(
        array('id' => 111, 'name' => 'Publisher1'),
        array('id' => 222, 'name' => 'Publisher2'),
      ),
      'Book' => array(
        array('id' => 111, 'title' => 'Book1'),
        array('id' => 222, 'title' => 'Book2'),
      ),
      'Chapter' => array(
        array('id' => 111, 'fk_book_id' => 111, 'name' => 'Chapter 1'),
        array('id' => 222, 'fk_chapter_id' => 111, 'name' => 'Chapter 1.1'),
      ),
    ));
  }

  public function testPermissionOverride() {
    TestUtil::startSession('userPermTest', 'user1');

    // reading User is allowed in user's config file (overrides config.ini)
    ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('User', 555));

    TestUtil::endSession();
  }

  /**
   * @expectedException wcmf\lib\security\AuthorizationException
   */
  public function testPermissionOk() {
    TestUtil::startSession('userPermTest', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();

    // modifying User is still forbidden
    $user = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('User', 555));
    $user->setValue('name', 'Tester');

    $transaction->commit();
    TestUtil::endSession();
  }

  public function testPermissionForOnlyOneInstance() {
    TestUtil::startSession('userPermTest', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // modifying Author is forbidden
    $transaction->begin();
    $author2 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Author', 222));
    $author2->setValue('name', 'Tester');
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    // modifying Author is forbidden except for Author:111
    $transaction->begin();
    $author1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Author', 111));
    $author1->setValue('name', 'Tester');
    $transaction->commit();

    TestUtil::endSession();
  }

  public function testPermissionOnAttributeGeneral() {
    TestUtil::startSession('userPermTest', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // modifying Author.stage is forbidden
    $transaction->begin();
    $author1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Author', 111));
    $author1->setValue('stage', 2);
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    // modifying Author.stage is forbidden
    $transaction->begin();
    $author2 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Author', 222));
    $author2->setValue('stage', 2);
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    TestUtil::endSession();
  }

  public function testPermissionOnAttributeForOnlyOneInstance() {
    TestUtil::startSession('userPermTest', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // modifying Publisher.name is forbidden
    $transaction->begin();
    $publisher1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Publisher', 222));
    $publisher1->setValue('name', 'Tester');
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    // modifying Publisher.name is forbidden except for Publisher:111.name
    $transaction->begin();
    $publisher2 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Publisher', 111));
    $publisher2->setValue('name', 'Tester');
    $transaction->commit();

    TestUtil::endSession();
  }

  public function testRead() {
    TestUtil::startSession('userPermTest', 'user1');

    // read list returns only one book
    $books = ObjectFactory::getInstance('persistenceFacade')->loadObjects('Book');
    $this->assertEquals(1, sizeof($books));
    $this->assertEquals('app.src.model.Book:111', $books[0]->getOID()->__toString());
    $forbidden = ObjectFactory::getInstance('persistenceFacade')->getTransaction()->getLoaded(new ObjectId('Book', 222));
    $this->assertNull($forbidden);

    TestUtil::endSession();
  }

  public function testInheritance() {
    TestUtil::startSession('userPermTest', 'user1');

    //$chapter1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 111));
    //$this->assertNull($chapter1);

    //$chapter11 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 222));
    //$this->assertNull($chapter11);

    TestUtil::endSession();
  }

  public function testGetOids() {
    TestUtil::startSession('userPermTest', 'user1');

    // read list returns only one book
    $oids = ObjectFactory::getInstance('persistenceFacade')->getOIDs('Book');
    $this->assertEquals(1, sizeof($oids));
    $this->assertEquals('app.src.model.Book:111', $oids[0]->__toString());

    TestUtil::endSession();
  }
}
?>