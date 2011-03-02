<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class NodeRelationTest extends WCMFTestCase
{
  private $oids = array();
  private $objects = array();

  protected function setUp()
  {
    $this->runAnonymous(true);
    // setup the object tree
    $this->oids = array(
      'page' => new ObjectId('Page', 300),
      'document' => new ObjectId('Document', 301),
      'childPage' => new ObjectId('Page', 302),
      'parentPage' => new ObjectId('Page', 303),
      'author' => new ObjectId('Author', 304),
      'titleImage' => new ObjectId('Image', 305),
      'normalImage' => new ObjectId('Image', 306)
    );
    $this->objects = array();
    foreach ($this->oids as $name => $oid) {
      $this->objects[$name] = $this->createTestObject($oid, array());
    }
    $page = $this->objects['page'];
    $page->addNode($this->objects['document']);
    $page->addNode($this->objects['childPage'], 'ChildPage');
    $page->addNode($this->objects['parentPage'], 'ParentPage');
    $page->addNode($this->objects['author']);
    $page->addNode($this->objects['titleImage'], 'TitleImage');
    $page->addNode($this->objects['normalImage'], 'NormalImage');
    foreach ($this->objects as $name => $obj) {
      $obj->save();
    }

    // reload objects
    $persistenceFacade = PersistenceFacade::getInstance();
    $this->objects = array();
    foreach ($this->oids as $name => $oid) {
      $this->objects[$name] = $persistenceFacade->load($oid);
    }

    $this->runAnonymous(false);
  }

  protected function tearDown()
  {
    $this->runAnonymous(true);
    foreach ($this->oids as $name => $oid) {
      $this->deleteTestObject($oid);
    }
    $this->runAnonymous(false);
  }

  public function testAddNode()
  {
    $this->runAnonymous(true);
    //$this->enableProfiler('Page');

    $persistenceFacade = PersistenceFacade::getInstance();
    $page = $persistenceFacade->load($this->oids['page'], 1);

    $curRelatives = $page->getChildrenEx(null, 'Document', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $this->oids['document']);

    $curRelatives = $page->getChildrenEx(null, null, 'Document');
    $this->assertTrue(sizeof($curRelatives) == 1);

    $curRelatives = $page->getChildrenEx(null, 'ChildPage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $this->oids['childPage']);

    $curRelatives = $page->getParentsEx(null, 'ParentPage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $this->oids['parentPage']);

    $curRelatives = $page->getParentsEx(null, 'Author', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $this->oids['author']);

    $curRelatives = $page->getChildrenEx(null, 'TitleImage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $this->oids['titleImage']);

    $curRelatives = $page->getChildrenEx(null, 'NormalImage', null);
    $this->assertTrue(sizeof($curRelatives) == 1);
    $this->assertTrue($curRelatives[0]->getOID() == $this->oids['normalImage']);

    $curRelatives = $page->getChildrenEx(null, null, 'Image');
    $this->assertTrue(sizeof($curRelatives) == 2);

    //$this->printProfile('Page');
    $this->runAnonymous(false);
  }

  public function testDeleteNode()
  {
    $this->runAnonymous(true);
    //$this->enableProfiler('Page');

    // delete all relations
    $page = $this->objects['page'];
    $page->deleteNode($this->objects['document']);
    $page->deleteNode($this->objects['childPage'], 'ChildPage');
    $page->deleteNode($this->objects['parentPage'], 'ParentPage');
    $page->deleteNode($this->objects['author']);
    $page->deleteNode($this->objects['titleImage'], 'TitleImage');
    $page->deleteNode($this->objects['normalImage'], 'NormalImage');
    foreach ($this->objects as $name => $obj) {
      $obj->save();
    }

    // test
    $persistenceFacade = PersistenceFacade::getInstance();
    $page = $persistenceFacade->load($this->oids['page'], 1);
    $this->assertTrue(sizeof($page->getChildrenEx(null, 'Document', null)) == 0);
    $this->assertTrue(sizeof($page->getChildrenEx(null, 'ChildPage', null)) == 0);
    $this->assertTrue(sizeof($page->getParentEx(null, 'ParentPage', null)) == 0);
    $this->assertTrue(sizeof($page->getParentEx(null, 'Author', null)) == 0);
    $this->assertTrue(sizeof($page->getChildrenEx(null, 'TitleImage', null)) == 0);
    $this->assertTrue(sizeof($page->getChildrenEx(null, 'NormalImage', null)) == 0);

    $document = $persistenceFacade->load($this->oids['document'], 1);
    $this->assertTrue(sizeof($document->getChildrenEx(null, 'Page', null)) == 0);

    $childPage = $persistenceFacade->load($this->oids['childPage'], 1);
    $this->assertTrue(sizeof($childPage->getParentEx(null, 'ParentPage', null)) == 0);

    $parentPage = $persistenceFacade->load($this->oids['parentPage'], 1);
    $this->assertTrue(sizeof($parentPage->getChildrenEx(null, 'ChildPage', null)) == 0);

    $author = $persistenceFacade->load($this->oids['author'], 1);
    $this->assertTrue(sizeof($author->getChildrenEx(null, 'Page', null)) == 0);

    $titleImage = $persistenceFacade->load($this->oids['titleImage'], 1);
    $this->assertTrue(sizeof($titleImage->getParentEx(null, 'TitlePage', null)) == 0);

    $normalImage = $persistenceFacade->load($this->oids['normalImage'], 1);
    $this->assertTrue(sizeof($normalImage->getParentEx(null, 'NormalPage', null)) == 0);

    //$this->printProfile('Page');
    $this->runAnonymous(false);
  }

  public function _testLoadSingle()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $page = $persistenceFacade->load($this->oids['page'], BUILDDEPTH_SINGLE);
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

  public function testDelete()
  {
    $this->runAnonymous(true);

    $page = $this->objects['page'];
    $page->delete();

    // test
    $persistenceFacade = PersistenceFacade::getInstance();
    $this->assertTrue($persistenceFacade->load($this->oids['page']) == null);
    $this->assertTrue($persistenceFacade->load($this->oids['document']) != null);
    $this->assertTrue($persistenceFacade->load($this->oids['childPage']) == null);
    $this->assertTrue($persistenceFacade->load($this->oids['parentPage']) != null);
    $this->assertTrue($persistenceFacade->load($this->oids['author']) != null);
    $this->assertTrue($persistenceFacade->load($this->oids['titleImage']) == null);
    $this->assertTrue($persistenceFacade->load($this->oids['normalImage']) != null);

    $this->runAnonymous(false);
  }
}
?>