<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");

class ManyToManyTest extends WCMFTestCase
{
  public function testRelation()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $userMapper = $persistenceFacade->getMapper('UserRDB');
    $relationDescription = $userMapper->getRelation('RoleRDB');
    $this->assertTrue($relationDescription->otherType == 'RoleRDB', "The type is RoleRDB");
    $this->assertTrue($relationDescription->otherRole == 'RoleRDB', "The role is RoleRDB");
    $this->assertTrue($relationDescription->otherMinMultiplicity == '0', "The minimum multiplicity is 0");
    $this->assertTrue($relationDescription->otherAggregationKind == 'none', "The aggregation kind is none");
    
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
    $this->assertTrue(in_array('RoleRDB', array_keys($children)), "RoleRDB is a possible child of UserRDB");

    $newUser = $persistenceFacade->create('UserRDB', 1);
    $role = $newUser->getFirstChild('RoleRDB');
    $this->assertTrue($role instanceof RoleRDB, "RoleRDB is a possible child of UserRDB");

    $this->runAnonymous(false);
  }

  public function testSave()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $userOid = new ObjectId('UserRDB', array(999999));
    $this->createTestObject($userOid, array());
    $roleOid = new ObjectId('RoleRDB', array(999990));
    $this->createTestObject($roleOid, array('name' => 'new role'));

    $user = $persistenceFacade->load($userOid, 1);
    $role = $persistenceFacade->load($roleOid, 1);
    $this->assertTrue(sizeof($user->getFirstChild('RoleRDB', null, null)) == 0, "No connection yet");
    $this->assertTrue(sizeof($role->getFirstChild('UserRDB', null, null)) == 0, "No connection yet");

    $user->addChild($role);
    $user->save();

    $oids = $persistenceFacade->getOids('NMUserRole',
      array('fk_user_id' => $user->getOID()->getFirstId(), 'fk_role_id' => $role->getOID()->getFirstId()));
    $this->assertTrue(sizeof($oids) == 1, "A connection was created");

    $user->setName('new user');
    $user->save();

    $oids = $persistenceFacade->getOids('NMUserRole',
      array('fk_user_id' => $user->getOID()->getFirstId(), 'fk_role_id' => $role->getOID()->getFirstId()));
        $this->assertTrue(sizeof($oids) == 1, "The connection is only created if not existing already");

    // cleanup
    $this->deleteTestObject($userOid);
    $oids = $persistenceFacade->getOids('NMUserRole',
      array('fk_user_id' => $user->getOID()->getFirstId(), 'fk_role_id' => $role->getOID()->getFirstId()));
        $this->assertTrue(sizeof($oids) == 0, "The connection was deleted");

    $this->deleteTestObject($roleOid);

    $this->runAnonymous(false);
  }
}
?>