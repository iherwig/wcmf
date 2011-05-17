<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class ManyToManyTest extends WCMFTestCase
{
  public function testRelation()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $userMapper = $persistenceFacade->getMapper('UserRDB');
    $relationDescription = $userMapper->getRelation('RoleRDB');
    $this->assertEquals('RoleRDB', $relationDescription->getOtherType(), "The type is RoleRDB");
    $this->assertEquals('RoleRDB', $relationDescription->getOtherRole(), "The role is RoleRDB");
    $this->assertEquals('0', $relationDescription->getOtherMinMultiplicity(), "The minimum multiplicity is 0");
    $this->assertEquals('none', $relationDescription->getOtherAggregationKind(), "The aggregation kind is none");

    $this->runAnonymous(false);
  }

  public function testLoad()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $user = $persistenceFacade->load(new ObjectId('UserRDB', 2), 1);
    $role = $user->getFirstChild('RoleRDB', null, null);
    $this->assertTrue($role instanceof RoleRDB, "The role is loaded as direct child");

    $user = $persistenceFacade->load(new ObjectId('UserRDB', 2), BUILDDEPTH_SINGLE);
    $user->loadChildren('RoleRDB');
    $role = $user->getFirstChild('RoleRDB', null, null);
    $this->assertTrue($role instanceof RoleRDB, "The role is loaded as direct child");

    $this->runAnonymous(false);
  }

  public function testCreate()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $newUser = $persistenceFacade->create('UserRDB', BUILDDEPTH_SINGLE);
    $children = $newUser->getPossibleChildren();
    $this->assertContains('RoleRDB', array_keys($children), "RoleRDB is a possible child of UserRDB");

    $newUser = $persistenceFacade->create('UserRDB', 1);
    $role = $newUser->getFirstChild('RoleRDB');
    $this->assertTrue($role instanceof RoleRDB, "RoleRDB is a possible child of UserRDB");

    $this->runAnonymous(false);
  }

  public function testSave()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();

    $transaction->begin();
    $userOid = new ObjectId('UserRDB', array(999999));
    $this->createTestObject($userOid, array());
    $roleOid = new ObjectId('RoleRDB', array(999990));
    $this->createTestObject($roleOid, array('name' => 'new role'));
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

    $this->deleteTestObject($userOid);
    $transaction->commit();

    $transaction->begin();
    $oids = $persistenceFacade->getOids('NMUserRole',
      array(new Criteria('NMUserRole', 'fk_user_id', '=', $user->getOID()->getFirstId()),
          new Criteria('NMUserRole', 'fk_role_id', '=', $role->getOID()->getFirstId()))
    );
    $this->assertEquals(0, sizeof($oids), "The connection was deleted");

    $this->deleteTestObject($roleOid);
    $transaction->commit();

    $this->runAnonymous(false);
  }
}
?>