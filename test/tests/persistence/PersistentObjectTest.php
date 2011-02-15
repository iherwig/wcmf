<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class PersistentObjectTest extends WCMFTestCase
{
  public function testLoadSingle()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $page = $persistenceFacade->load(new ObjectId('Page', array(1)), BUILDDEPTH_SINGLE);
    $page->loadChildren('Document', 1);
    /*
    foreach($page->getValueNames() as $name) {
      $value = $page->getValue($name);
      echo $name.": ".$value."(".sizeof($value).")\n";
    }
    */
    $this->runAnonymous(false);
  }

  function _testCreateRandom() {
    $this->runAnonymous(true);
    $alphanum = "abcdefghijkmnpqrstuvwxyz23456789";
    $pf = PersistenceFacade::getInstance();
    for ($i=0; $i<100; $i++) {
      $doc = $pf->create('Page', BUILDDEPTH_SINGLE);
      $inc = 1;
      while ($inc < 15){
        $alphanum = $alphanum.'abcdefghijkmnpqrstuvwxyz23456789';
        $inc++;
      }
      $title = substr(str_shuffle($alphanum), 0, 15);
      $doc->setName(ucfirst($title));
      $doc->save();
    }
    $this->runAnonymous(false);
  }

  public function _testLoadMany()
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