<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class PersistentObjectTest extends PHPUnit_Framework_TestCase
{
  function testCreateRandom()
  {
    TestUtil::runAnonymous(true);
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
    TestUtil::runAnonymous(false);
  }

  function testCopyValues()
  {
    TestUtil::runAnonymous(true);
    $page1 = new Page(new ObjectId('Page', 123));
    $page1->setName('Page 1');
    $page1->setCreated(null);

    // copy values without pks
    $page21 = new Page(new ObjectId('Page', 234));
    $page21->setName('Page 2');
    $page1->setCreated('2011-05-31');
    $page1->copyValues($page21, false);
    $this->assertEquals('Page:234', $page21->getOID()->__toString());
    $this->assertEquals('Page 1', $page21->getName());
    $this->assertEquals('2011-05-31', $page21->getCreated());

    // copy values without pks
    $page22 = new Page(new ObjectId('Page', 234));
    $page22->setName('Page 2');
    $page1->setCreated('2011-05-31');
    $page1->copyValues($page22, true);
    $this->assertEquals('Page:123', $page22->getOID()->__toString());
    $this->assertEquals('Page 1', $page22->getName());
    $this->assertEquals('2011-05-31', $page22->getCreated());

    TestUtil::runAnonymous(false);
  }

  function testMergeValues()
  {
    TestUtil::runAnonymous(true);
    $page1 = new Page(new ObjectId('Page', 123));
    $page1->setName('Page 1');
    $page1->setCreated('2011-05-31');
    $page1->setCreator('admin');

    $page2 = new Page(new ObjectId('Page', 234));
    $page2->setName('Page 2');
    $page1->setCreated(null);
    $page2->mergeValues($page1);
    $this->assertEquals('Page:234', $page2->getOID()->__toString());
    $this->assertEquals('Page 2', $page2->getName());
    $this->assertEquals(null, $page2->getCreated());
    $this->assertEquals('admin', $page2->getCreator());

    TestUtil::runAnonymous(false);
  }

  function testClearValues()
  {
    TestUtil::runAnonymous(true);
    $page1 = new Page(new ObjectId('Page', 123));
    $page1->setName('Page 1');
    $page1->setCreated('2011-05-31');

    $page1->clearValues();
    $this->assertEquals('Page:123', $page1->getOID()->__toString());
    $this->assertEquals(null, $page1->getName());
    $this->assertEquals(null, $page1->getCreated());

    TestUtil::runAnonymous(false);
  }

  public function testLoadPaging()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $pagingInfo = new PagingInfo(10);
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE, null, null, $pagingInfo);
    $this->assertEquals(10, sizeof($documents));

    TestUtil::runAnonymous(false);
  }

  /**
   * @group performance
   */
  public function testLoadManyWithAllAttributes()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $start = time();
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE);
    Log::info("Loaded ".sizeof($documents)." documents with all attributes in ".(time()-$start)." seconds", __CLASS__);
    TestUtil::runAnonymous(false);
  }

  /**
   * @group performance
   */
  public function testLoadManyWithOneAttribute()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $start = time();
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE,
            null, null, null, array('Document' => array('id')));
    Log::info("Loaded ".sizeof($documents)." documents with one attribute in ".(time()-$start)." seconds", __CLASS__);
    TestUtil::runAnonymous(false);
  }
}
?>