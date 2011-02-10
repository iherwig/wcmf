<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");

class PersistentObjectTest extends WCMFTestCase
{
  public function testDelete()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $oid = new ObjectId('UserRDB', array(0));
    $this->createTestObject($oid, array());
    $user = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
    $user->delete();
    $this->assertTrue($persistenceFacade->load($oid, BUILDDEPTH_SINGLE) == null, "The user was deleted");

    $this->runAnonymous(false);
  }
}
?>