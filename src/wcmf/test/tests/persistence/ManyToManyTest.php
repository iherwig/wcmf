<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use app\src\model\wcmf\Role;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * ManyToManyTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ManyToManyTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
        ['id' => 50, 'login' => 'user1', 'active' => 1, 'super_user' => 0, 'config' => ''],
        ['id' => 60, 'login' => 'user2', 'active' => 1, 'super_user' => 0, 'config' => ''],
      ],
      'Role' => [
        ['id' => 0, 'name' => 'administrators'],
        ['id' => 51, 'name' => 'group1'],
        ['id' => 61, 'name' => 'group2'],
      ],
      'NMUserRole' => [
        ['fk_user_id' => 0, 'fk_role_id' => 0],
        ['fk_user_id' => 50, 'fk_role_id' => 51],
      ],
    ]);
  }

  public function testRelation() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $userMapper = $persistenceFacade->getMapper('User');
    $relationDescription = $userMapper->getRelation('Role');
    $this->assertEquals('app.src.model.wcmf.Role', $relationDescription->getOtherType(), "The type is Role");
    $this->assertEquals('Role', $relationDescription->getOtherRole(), "The role is Role");
    $this->assertEquals('0', $relationDescription->getOtherMinMultiplicity(), "The minimum multiplicity is 0");
    $this->assertEquals('none', $relationDescription->getOtherAggregationKind(), "The aggregation kind is none");

    TestUtil::endSession();
  }

  public function testLoad() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $user1 = $persistenceFacade->load(new ObjectId('User', 50), 1);
    $role1 = $user1->getFirstChild('Role', null, null);
    $this->assertTrue($role1 instanceof Role, "The role is loaded as direct child");

    $user2 = $persistenceFacade->load(new ObjectId('User', 50), BuildDepth::SINGLE);
    $user2->loadChildren('Role');
    $role2 = $user2->getFirstChild('Role', null, null);
    $this->assertTrue($role2 instanceof Role, "The role is loaded as direct child");

    TestUtil::endSession();
  }

  public function testCreate() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $newUser1 = $persistenceFacade->create('User', BuildDepth::SINGLE);
    $children = $newUser1->getPossibleChildren();
    $this->assertContains('Role', array_keys($children), "Role is a possible child of User");

    $newUser2 = $persistenceFacade->create('User', 1);
    $role = $newUser2->getFirstChild('Role');
    $this->assertTrue($role instanceof Role, "Role is a possible child of User");

    TestUtil::endSession();
  }

  public function testSave() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $userId = 60;
    $roleId = 61;

    $transaction->begin();
    $user = $persistenceFacade->load(new ObjectId('User', [$userId]), 1);
    $role = $persistenceFacade->load(new ObjectId('Role', [$roleId]), 1);
    $this->assertNull($user->getFirstChild('Role', null, null), "No connection yet");
    $this->assertNull($role->getFirstChild('User', null, null), "No connection yet");

    // add role first time
    $user->addNode($role);
    $transaction->commit();

    $transaction->begin();
    $numNM1 = $this->getConnection()->getRowCount('NMUserRole', "fk_user_id = ".$userId." AND fk_role_id = ".$roleId);
    $this->assertEquals(1, $numNM1, "A connection was created");

    // add role second time
    $user->addNode($role);
    $transaction->commit();

    $transaction->begin();
    $numNM2 = $this->getConnection()->getRowCount('NMUserRole', "fk_user_id = ".$userId." AND fk_role_id = ".$roleId);
    $this->assertEquals(1, $numNM2, "The connection is only created if not existing already");

    // delete user
    $user->delete();
    $transaction->commit();

    $transaction->begin();
    $numNM3 = $this->getConnection()->getRowCount('NMUserRole', "fk_user_id = ".$userId." AND fk_role_id = ".$roleId);
    $this->assertEquals(0, $numNM3, "The connection was deleted");
    $transaction->commit();

    TestUtil::endSession();
  }
}
?>