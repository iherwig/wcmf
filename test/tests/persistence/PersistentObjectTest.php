<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class PersistentObjectTest extends WCMFTestCase
{
  public function testAddNode()
  {
    $this->runAnonymous(true);

    // setup
    $oids = array(
      'page' => new ObjectId('Page', 300),
      'document' => new ObjectId('Document', 301),
      'childPage' => new ObjectId('Page', 302),
      'parentPage' => new ObjectId('Page', 303),
      'author' => new ObjectId('Author', 304),
      'titleImage' => new ObjectId('Image', 305),
      'normalImage' => new ObjectId('Image', 306)
    );
    $objects = array();
    foreach ($oids as $name => $oid) {
      $objects[$name] = $this->createTestObject($oid, array());
    }
    $page = $objects['page'];
    $page->addNode($objects['document']);
    $page->addNode($objects['childPage'], 'ChildPage');
    $page->addNode($objects['parentPage'], 'ParentPage');
    $page->addNode($objects['author']);
    $page->addNode($objects['titleImage'], 'TitleImage');
    $page->addNode($objects['normalImage'], 'NormalImage');
    foreach ($objects as $name => $obj) {
      $obj->save();
    }

    // test
    $persistenceFacade = PersistenceFacade::getInstance();
    $loadedPage = $persistenceFacade->load($oids['page'], 1);

    $curRelatives = $loadedPage->getChildrenEx(null, 'Document', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $oids['document']);

    $curRelatives = $loadedPage->getChildrenEx(null, null, 'Document');
    $this->assertTrue(sizeof($curRelatives) == 1);

    $curRelatives = $loadedPage->getChildrenEx(null, 'ChildPage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $oids['childPage']);

    $curRelatives = $loadedPage->getParentsEx(null, 'ParentPage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $oids['parentPage']);

    $curRelatives = $loadedPage->getParentsEx(null, 'Author', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $oids['author']);

    $curRelatives = $loadedPage->getChildrenEx(null, 'TitleImage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $oids['titleImage']);

    $curRelatives = $loadedPage->getChildrenEx(null, 'NormalImage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $oids['normalImage']);

    $curRelatives = $loadedPage->getChildrenEx(null, null, 'Image');
    $this->assertTrue(sizeof($curRelatives) == 2);

    // teardown
    foreach ($oids as $name => $oid) {
      $this->deleteTestObject($oid);
    }
  }

  public function _testLoadSingle()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $page = $persistenceFacade->load(new ObjectId('Page', array(1)), BUILDDEPTH_SINGLE);
    $page->loadChildren('Document', 1);
    $document = $page->getFirstChild('Document');
    echo "title: ".$document->getTitle()."\n";
    //*
    foreach($page->getValueNames() as $name) {
      $value = $page->getValue($name);
      echo $name.": ".$value."(".sizeof($value).")\n";
    }
    //*/
    $this->runAnonymous(false);
  }

  public function _testCaching()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $persistenceFacade->setCaching(true);
    $time = time();

    $page1 = $persistenceFacade->load(new ObjectId('Page', array(1)), BUILDDEPTH_SINGLE);
    $page1->setName('testing'.$time);

    $page2 = $persistenceFacade->load(new ObjectId('Page', array(1)), BUILDDEPTH_SINGLE);
    $this->assertTrue($page2->getName() == 'testing'.$time);

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