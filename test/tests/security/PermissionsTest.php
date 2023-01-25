<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\security;

use app\src\model\Chapter;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * PermissionsTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionsTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 555, 'login' => 'userPermTest', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02', 'active' => 1, 'super_user' => 0, 'config' => 'permissions.ini']
      ],
      'NMUserRole' => [
        ['fk_user_id' => 555, 'fk_role_id' => 555],
      ],
      'Role' => [
        ['id' => 555, 'name' => 'tester'],
      ],
      'Author' => [
        ['id' => 111, 'name' => 'Author1'],
        ['id' => 222, 'name' => 'Author2'],
      ],
      'Publisher' => [
        ['id' => 111, 'name' => 'Publisher1'],
        ['id' => 222, 'name' => 'Publisher2'],
      ],
      'Book' => [
        ['id' => 111, 'title' => 'Book1', 'creator' => 'userPermTest'],
        ['id' => 222, 'title' => 'Book2', 'creator' => ''],
      ],
      'Chapter' => [
        ['id' => 111, 'fk_chapter_id' => null, 'fk_book_id' => 111, 'name' => 'Chapter 1'],
        ['id' => 222, 'fk_chapter_id' => 111, 'fk_book_id' => null, 'name' => 'Chapter 1.1'],
        ['id' => 333, 'fk_chapter_id' => 222, 'fk_book_id' => null, 'name' => 'Chapter 1.1.1'],
        ['id' => 444, 'fk_chapter_id' => null, 'fk_book_id' => 111, 'name' => 'Chapter 2'],
        ['id' => 555, 'fk_chapter_id' => 111, 'fk_book_id' => null, 'name' => 'Chapter 1.2'],
        ['id' => 666, 'fk_chapter_id' => 555, 'fk_book_id' => null, 'name' => 'Chapter 1.2.1'],
      ],
      'Permission' => [
        ['id' => 111, 'resource' => 'Chapter:111', 'context' => 'test', 'action' => 'delete', 'roles' => '+* +users -administrators'],
      ],
    ]);
  }

  public function te_stPermissionOverride() {
    TestUtil::startSession('userPermTest', 'user1');

    // reading User is allowed in user's config file (overrides config.ini)
    ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('User', 555));

    TestUtil::endSession();
  }

  /**
   * @expectedException wcmf\lib\security\AuthorizationException
   */
  public function te_stPermissionOk() {
    TestUtil::startSession('userPermTest', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();

    // modifying User is still forbidden
    $user = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('User', 555));
    $user->setValue('name', 'Tester');

    $transaction->commit();
    TestUtil::endSession();
  }

  public function te_stPermissionForOnlyOneInstance() {
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

  public function te_stPermissionOnAttributeGeneral() {
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

  public function te_stPermissionOnAttributeForOnlyOneInstance() {
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

  public function testPermissionOnRelationGeneral() {
    TestUtil::startSession('userPermTest', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // modifying Book.chapter is forbidden
    $transaction->begin();
    $book1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Book', 111));
    $book1->setValue('chapter', []);
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    // add to Book.chapter is forbidden
    $transaction->begin();
    $book1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Book', 111));
    $book1->addNode(new Chapter(null, ['name' => 'Chapter New']));
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    // remove from Book.chapter is forbidden
    $transaction->begin();
    $book1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Book', 111), 2);
    $book1->deleteNode($book1->getFirstChild('Chapter'));
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    TestUtil::endSession();
  }

  public function te_stRead() {
    TestUtil::startSession('userPermTest', 'user1');

    // read list returns only one book
    $books = ObjectFactory::getInstance('persistenceFacade')->loadObjects('Book');
    $this->assertEquals(1, sizeof($books));
    $this->assertEquals('app.src.model.Book:111', $books[0]->getOID()->__toString());
    $forbidden = ObjectFactory::getInstance('persistenceFacade')->getTransaction()->getLoaded(new ObjectId('Book', 222));
    $this->assertNull($forbidden);

    TestUtil::endSession();
  }

  public function te_stInheritance() {
    TestUtil::startSession('userPermTest', 'user1');

    // chapter 1 and sub chapters are forbidden
    $chapter1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 111));
    $this->assertNull($chapter1);
    $chapter11 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 222));
    $this->assertNull($chapter11);
    $chapter111 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 333));
    $this->assertNull($chapter111);
    $chapter12 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 555));
    $this->assertNotNull($chapter12);
    $chapter121 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 666));
    $this->assertNotNull($chapter121);

    $chapter2 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Chapter', 444));
    $this->assertNotNull($chapter2);

    TestUtil::endSession();
  }

  public function te_stGetOids() {
    TestUtil::startSession('userPermTest', 'user1');

    // read list returns only one book
    $oids = ObjectFactory::getInstance('persistenceFacade')->getOIDs('Book');
    $this->assertEquals(1, sizeof($oids));
    $this->assertEquals('app.src.model.Book:111', $oids[0]->__toString());

    TestUtil::endSession();
  }

  public function te_stCreatorPermission() {
    TestUtil::startSession('userPermTest', 'user1');

    $permissionManager = ObjectFactory::getInstance('permissionManager');

    // test
    $this->assertTrue($permissionManager->authorize('Book:111', '', 'update'));
    $this->assertTrue($permissionManager->authorize('Book:111.title', '', 'update'));
    $this->assertFalse($permissionManager->authorize('Book:222', '', 'update'));
    $this->assertFalse($permissionManager->authorize('Book:222.title', '', 'update'));

    $newBook = ObjectFactory::getInstance('persistenceFacade')->create('Book');
    $this->assertTrue($permissionManager->authorize($newBook->getOID()->__toString(), '', 'update'));

    TestUtil::endSession();
  }

  public function te_stCustomPermission() {
    TestUtil::startSession('userPermTest', 'user1');

    $permissionManager = ObjectFactory::getInstance('permissionManager');

    // test
    $this->assertTrue($permissionManager->authorize('customPermission', '', 'start'));
    $this->assertFalse($permissionManager->authorize('customPermission', '', 'stop'));

    TestUtil::endSession();
  }

  public function te_stGetPermissions() {
    TestUtil::startSession('userPermTest', 'user1');

    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissions = $permissionManager->getPermissions('Chapter:111', 'test', 'delete');

    // test
    $this->assertTrue(isset($permissions['allow']));
    $this->assertTrue(isset($permissions['deny']));
    $this->assertTrue(isset($permissions['default']));
    $this->assertEquals(['users'], $permissions['allow']);
    $this->assertEquals(['administrators'], $permissions['deny']);
    $this->assertTrue($permissions['default']);

    TestUtil::endSession();
  }

  public function te_stSetPermissions() {
    TestUtil::startSession('userPermTest', 'user1');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->setPermissions('Chapter:111', 'test', 'delete', [
      'allow' => ['tester', 'administrators'],
      'deny' => ['users'],
      'default' => false
    ]);
    $transaction->commit();

    // test
    $permission = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Permission', 111));
    $this->assertEquals('-* +tester +administrators -users', $permission->getValue('roles'));

    TestUtil::endSession();
  }

  public function te_stSetPermissionsNull() {
    TestUtil::startSession('userPermTest', 'user1');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->setPermissions('Chapter:111', 'test', 'delete', null);
    $transaction->commit();

    // test
    $permission = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Permission', 111));
    $this->assertNull($permission);

    TestUtil::endSession();
  }

  public function te_stPermissionCreateNew() {
    TestUtil::startSession('userPermTest', 'user1');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->createPermission('Chapter:222', 'test', 'delete', 'tester',
            PermissionManager::PERMISSION_MODIFIER_DENY);
    $transaction->commit();

    // test
    $oids = ObjectFactory::getInstance('persistenceFacade')->getOIDs('Permission');
    $this->assertEquals(2, sizeof($oids));

    $query = new ObjectQuery('Permission', __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate('Permission');
    $tpl->setValue('resource', Criteria::asValue('=', 'Chapter:222'));
    $tpl->setValue('context', Criteria::asValue('=', 'test'));
    $tpl->setValue('action', Criteria::asValue('=', 'delete'));
    $permissions = $query->execute(BuildDepth::SINGLE);
    $this->assertEquals(1, sizeof($permissions));
    $this->assertEquals('-tester', $permissions[0]->getValue('roles'));

    TestUtil::endSession();
  }

  public function te_stPermissionAddToExisting() {
    TestUtil::startSession('userPermTest', 'user1');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->createPermission('Chapter:111', 'test', 'delete', 'tester',
            PermissionManager::PERMISSION_MODIFIER_ALLOW);
    $transaction->commit();

    // test
    $permission = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Permission', 111));
    $this->assertEquals('+* +users -administrators +tester', $permission->getValue('roles'));

    TestUtil::endSession();
  }

  public function te_stPermissionRemoveFromExisting() {
    TestUtil::startSession('userPermTest', 'user1');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->removePermission('Chapter:111', 'test', 'delete', 'users');
    $transaction->commit();

    // test
    $permission = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Permission', 111));
    $this->assertEquals('+* -administrators', $permission->getValue('roles'));

    TestUtil::endSession();
  }

  public function te_stPermissionDeleteAfterRemoveLast() {
    TestUtil::startSession('userPermTest', 'user1');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->removePermission('Chapter:111', 'test', 'delete', 'users');
    $permissionManager->removePermission('Chapter:111', 'test', 'delete', 'administrators');
    $permissionManager->removePermission('Chapter:111', 'test', 'delete', '*');
    $transaction->commit();

    // test
    $permission = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Permission', 111));
    $this->assertNull($permission);

    TestUtil::endSession();
  }

  public function te_stTempPermissionInstance() {
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    $oid = new ObjectId('Chapter', 200);
    $this->assertFalse($permissionManager->authorize($oid, '', PersistenceAction::READ));

    $result = $permissionManager->withTempPermissions(function() use ($permissionManager, $oid) {
      return $permissionManager->authorize($oid, '', PersistenceAction::READ);
    }, [$oid, '', PersistenceAction::READ]);
    $this->assertTrue($result);
  }

  public function te_stTempPermissionType() {
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    $oid = new ObjectId('Chapter', 200);
    $this->assertFalse($permissionManager->authorize($oid, '', PersistenceAction::READ));

    $result = $permissionManager->withTempPermissions(function() use ($permissionManager, $oid) {
      $permissionManager->authorize($oid, '', PersistenceAction::READ);
    }, ['app.src.model.Chapter', '', PersistenceAction::READ]);
    $this->assertTrue($result);
  }
}
?>