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
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * ManyToManyTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ManyToManyTest extends \PHPUnit_Framework_TestCase {

  public function testRelation() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $userMapper = $persistenceFacade->getMapper('UserRDB');
    $relationDescription = $userMapper->getRelation('RoleRDB');
    $this->assertEquals('RoleRDB', $relationDescription->getOtherType(), "The type is RoleRDB");
    $this->assertEquals('RoleRDB', $relationDescription->getOtherRole(), "The role is RoleRDB");
    $this->assertEquals('0', $relationDescription->getOtherMinMultiplicity(), "The minimum multiplicity is 0");
    $this->assertEquals('none', $relationDescription->getOtherAggregationKind(), "The aggregation kind is none");

    TestUtil::runAnonymous(false);
  }

  public function testLoad() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $user = $persistenceFacade->load(new ObjectId('UserRDB', 2), 1);
    $role = $user->getFirstChild('RoleRDB', null, null);
    $this->assertTrue($role instanceof RoleRDB, "The role is loaded as direct child");

    $user = $persistenceFacade->load(new ObjectId('UserRDB', 2), BuildDepth::SINGLE);
    $user->loadChildren('RoleRDB');
    $role = $user->getFirstChild('RoleRDB', null, null);
    $this->assertTrue($role instanceof RoleRDB, "The role is loaded as direct child");

    TestUtil::runAnonymous(false);
  }

  public function testCreate() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $newUser = $persistenceFacade->create('UserRDB', BuildDepth::SINGLE);
    $children = $newUser->getPossibleChildren();
    $this->assertContains('RoleRDB', array_keys($children), "RoleRDB is a possible child of UserRDB");

    $newUser = $persistenceFacade->create('UserRDB', 1);
    $role = $newUser->getFirstChild('RoleRDB');
    $this->assertTrue($role instanceof RoleRDB, "RoleRDB is a possible child of UserRDB");

    TestUtil::runAnonymous(false);
  }

  public function testSave() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    $userOid = new ObjectId('UserRDB', array(999999));
    TestUtil::createTestObject($userOid, array());
    $roleOid = new ObjectId('RoleRDB', array(999990));
    TestUtil::createTestObject($roleOid, array('name' => 'new role'));
    $transaction->commit();

    $transaction->begin();
    $user = $persistenceFacade->load($userOid, 1);
    $role = $persistenceFacade->load($roleOid, 1);
    $this->assertEquals(0, sizeof($user->getFirstChild('RoleRDB', null, null)), "No connection yet");
    $this->assertEquals(0, sizeof($role->getFirstChild('UserRDB', null, null)), "No connection yet");

    $user->addNode($role);
    $transaction->commit();

    $transaction->begin();
    $oids = $persistenceFacade->getOids('NMUserRole',
      array(new Criteria('NMUserRole', 'fk_user_id', '=', $user->getOID()->getFirstId()),
          new Criteria('NMUserRole', 'fk_role_id', '=', $role->getOID()->getFirstId()))
    );
    $this->assertEquals(1, sizeof($oids), "A connection was created");

    $user->setName('new user');
    $transaction->commit();

    $transaction->begin();
    $oids = $persistenceFacade->getOids('NMUserRole',
      array(new Criteria('NMUserRole', 'fk_user_id', '=', $user->getOID()->getFirstId()),
          new Criteria('NMUserRole', 'fk_role_id', '=', $role->getOID()->getFirstId()))
    );
    $this->assertEquals(1, sizeof($oids), "The connection is only created if not existing already");

    TestUtil::deleteTestObject($userOid);
    $transaction->commit();

    $transaction->begin();
    $oids = $persistenceFacade->getOids('NMUserRole',
      array(new Criteria('NMUserRole', 'fk_user_id', '=', $user->getOID()->getFirstId()),
          new Criteria('NMUserRole', 'fk_role_id', '=', $role->getOID()->getFirstId()))
    );
    $this->assertEquals(0, sizeof($oids), "The connection was deleted");

    TestUtil::deleteTestObject($roleOid);
    $transaction->commit();

    TestUtil::runAnonymous(false);
  }
}
?>