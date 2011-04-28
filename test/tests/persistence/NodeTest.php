<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class NodeTest extends WCMFTestCase
{
  public function _testCaching()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $persistenceFacade->setCaching(true);
    $time = time();

    $page1 = $persistenceFacade->load($this->oids['page'], BUILDDEPTH_SINGLE);
    $page1->setName('testing'.$time);

    $page2 = $persistenceFacade->load($this->oids['page'], BUILDDEPTH_SINGLE);
    $this->assertEquals('testing'.$time, $page2->getName());

    $this->runAnonymous(false);
  }

  function testCreateRandom()
  {
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

  public function testLoadPaging()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $pagingInfo = new PagingInfo(10);
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE, null, null, $pagingInfo);
    $this->assertEquals(10, sizeof($documents));

    $this->runAnonymous(false);
  }

  public function _testLoadMany()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE);
    echo sizeof($documents)."\n";
    /*
    foreach($documents as $document) {
      if($document->getId() == 3) {
        foreach($document->getValueNames() as $name) {
          $value = $document->getValue($name);
          echo $name.": ".$value."(".sizeof($value).")\n";
        }
      }
    }
    //*/
    $this->runAnonymous(false);
  }
}
?>