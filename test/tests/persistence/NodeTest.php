<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class NodeTest extends WCMFTestCase
{
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

  public function testLoadManyWithAllAttributes()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE);
    echo "Loaded documents: ".sizeof($documents);
    $this->runAnonymous(false);
  }

  public function testLoadManyWithOneAttribute()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE,
            null, null, null, array('Document' => array('id')));
    echo "Loaded documents: ".sizeof($documents);
    $this->runAnonymous(false);
  }
}
?>