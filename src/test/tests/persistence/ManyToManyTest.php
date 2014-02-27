<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace test\tests\persistence;

use app\src\model\wcmf\Role;

use test\lib\ArrayDataSet;
use test\lib\DatabaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

/**
 * ManyToManyTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ManyToManyTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array('id' => 1),
      ),
      'user' => array(
        array('id' => 50, 'login' => 'user1'),
        array('id' => 60, 'login' => 'user2'),
      ),
      'role' => array(
        array('id' => 51, 'name' => 'group1'),
        array('id' => 61, 'name' => 'group2'),
      ),
      'nm_user_role' => array(
        array('fk_user_id' => 50, 'fk_role_id' => 51),
      ),
    ));
  }

  public function testRelation() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $userMapper = $persistenceFacade->getMapper('User');
    $relationDescription = $userMapper->getRelation('Role');
    $this->assertEquals('app.src.model.wcmf.Role', $relationDescription->getOtherType(), "The type is Role");
    $this->assertEquals('Role', $relationDescription->getOtherRole(), "The role is Role");
    $this->assertEquals('0', $relationDescription->getOtherMinMultiplicity(), "The minimum multiplicity is 0");
    $this->assertEquals('none', $relationDescription->getOtherAggregationKind(), "The aggregation kind is none");

    TestUtil::runAnonymous(false);
  }

  public function testLoad() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $user1 = $persistenceFacade->load(new ObjectId('User', 50), 1);
    $role1 = $user1->getFirstChild('Role', null, null);
    $this->assertTrue($role1 instanceof Role, "The role is loaded as direct child");

    $user2 = $persistenceFacade->load(new ObjectId('User', 50), BuildDepth::SINGLE);
    $user2->loadChildren('Role');
    $role2 = $user2->getFirstChild('Role', null, null);
    $this->assertTrue($role2 instanceof Role, "The role is loaded as direct child");

    TestUtil::runAnonymous(false);
  }

  public function testCreate() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $newUser1 = $persistenceFacade->create('User', BuildDepth::SINGLE);
    $children = $newUser1->getPossibleChildren();
    $this->assertContains('Role', array_keys($children), "Role is a possible child of User");

    $newUser2 = $persistenceFacade->create('User', 1);
    $role = $newUser2->getFirstChild('Role');
    $this->assertTrue($role instanceof Role, "Role is a possible child of User");

    TestUtil::runAnonymous(false);
  }

  public function testSave() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $userId = 60;
    $roleId = 61;

    $transaction->begin();
    $user = $persistenceFacade->load(new ObjectId('User', array($userId)), 1);
    $role = $persistenceFacade->load(new ObjectId('Role', array($roleId)), 1);
    $this->assertEquals(0, sizeof($user->getFirstChild('Role', null, null)), "No connection yet");
    $this->assertEquals(0, sizeof($role->getFirstChild('User', null, null)), "No connection yet");

    // add role first time
    $user->addNode($role);
    $transaction->commit();

    $transaction->begin();
    $numNM1 = $this->getConnection()->getRowCount('nm_user_role', "fk_user_id = ".$userId." AND fk_role_id = ".$roleId);
    $this->assertEquals(1, $numNM1, "A connection was created");

    // add role second time
    $user->addNode($role);
    $transaction->commit();

    $transaction->begin();
    $numNM2 = $this->getConnection()->getRowCount('nm_user_role', "fk_user_id = ".$userId." AND fk_role_id = ".$roleId);
    $this->assertEquals(1, $numNM2, "The connection is only created if not existing already");

    // delete user
    $user->delete();
    $transaction->commit();

    $transaction->begin();
    $numNM3 = $this->getConnection()->getRowCount('nm_user_role', "fk_user_id = ".$userId." AND fk_role_id = ".$roleId);
    $this->assertEquals(0, $numNM3, "The connection was deleted");
    $transaction->commit();

    TestUtil::runAnonymous(false);
  }
}
?>