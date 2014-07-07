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
        array('id' => 555, 'login' => 'user1', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02',
            'config' => 'permissions.ini')
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 555, 'fk_role_id' => 555),
      ),
      'Role' => array(
        array('id' => 555, 'name' => 'tester'),
      ),
      'Author' => array(
        array('id' => 111),
        array('id' => 222),
      ),
      'Publisher' => array(
        array('id' => 111),
        array('id' => 222),
      ),
    ));
  }

  public function testPermissionOverride() {
    TestUtil::startSession('user1', 'user1');

    // reading is allowed in user's config file (overrides config.ini)
    ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('User', 555));

    TestUtil::endSession();
  }

  /**
   * @expectedException wcmf\lib\security\AuthorizationException
   */
  public function testPermissionOk() {
    TestUtil::startSession('user1', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();

    // modifying is still forbidden
    $user = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('User', 555));
    $user->setValue('name', 'Tester');

    $transaction->commit();
    TestUtil::endSession();
  }

  public function testPermissionForOnlyOneInstance() {
    TestUtil::startSession('user1', 'user1');
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

    // Author:111 is allowed
    $transaction->begin();
    $author1 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Author', 111));
    $author1->setValue('name', 'Tester');
    $transaction->commit();

    // Author:222 is forbidden
    $transaction->begin();
    $author2 = ObjectFactory::getInstance('persistenceFacade')->load(new ObjectId('Author', 222));
    $author2->setValue('name', 'Tester');
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (\wcmf\lib\security\AuthorizationException $ex) {
      $transaction->rollback();
    }

    TestUtil::endSession();
  }

}
?>