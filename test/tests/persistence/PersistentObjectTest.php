<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class PersistentObjectTest extends WCMFTestCase
{
  public function _testLoadSingle()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $page = $persistenceFacade->load(new ObjectId('Page', array(1)), BUILDDEPTH_SINGLE);
    foreach($page->getValueNames() as $name) {
      $value = $page->getValue($name);
      echo $name.": ".$value."(".sizeof($value).")\n";
    }

    $this->runAnonymous(false);
  }

  public function testLoadMany()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE);
    echo sizeof($documents)."\n";
    foreach($documents as $document) {
      if($document->getId() == 3) {
        foreach($document->getValueNames() as $name) {
          $value = $document->getValue($name);
          echo $name.": ".$value."(".sizeof($value).")\n";
        }
      }
    }

    $this->runAnonymous(false);
  }

  public function _testDelete()
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