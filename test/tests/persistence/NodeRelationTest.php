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

    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();

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
    // commit changes
    $transaction->commit();

    $this->runAnonymous(false);
  }

  protected function tearDown()
  {
    $this->runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    foreach ($this->oids as $name => $oid) {
      $this->deleteTestObject($oid);
    }
    $transaction->commit();
    $this->runAnonymous(false);
  }

  public function testAddNode()
  {
    $this->runAnonymous(true);
    //$this->enableProfiler('Page');

    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page = $persistenceFacade->load($this->oids['page'], 1);

    $curRelatives = $page->getChildrenEx(null, 'Document', null);
    $this->assertEquals(1, sizeof($curRelatives));
    $this->assertEquals($this->oids['document'], $curRelatives[0]->getOID());

    $curRelatives = $page->getChildrenEx(null, null, 'Document');
    $this->assertEquals(1, sizeof($curRelatives));

    $curRelatives = $page->getChildrenEx(null, 'ChildPage', null);
    $this->assertEquals(1, sizeof($curRelatives));
    $this->assertEquals($this->oids['childPage'], $curRelatives[0]->getOID());

    $curRelatives = $page->getParentsEx(null, 'ParentPage', null);
    $this->assertEquals(1, sizeof($curRelatives));
    $this->assertEquals($this->oids['parentPage'], $curRelatives[0]->getOID());

    $curRelatives = $page->getParentsEx(null, 'Author', null);
    $this->assertEquals(1, sizeof($curRelatives));
    $this->assertEquals($this->oids['author'], $curRelatives[0]->getOID());

    $curRelatives = $page->getChildrenEx(null, 'TitleImage', null);
    $this->assertEquals(1, sizeof($curRelatives));
    $this->assertEquals($this->oids['titleImage'], $curRelatives[0]->getOID());

    $curRelatives = $page->getChildrenEx(null, 'NormalImage', null);
    $this->assertEquals(1, sizeof($curRelatives));
    $this->assertEquals($this->oids['normalImage'], $curRelatives[0]->getOID());

    $curRelatives = $page->getChildrenEx(null, null, 'Image');
    $this->assertEquals(2, sizeof($curRelatives));
    $transaction->rollback();

    //$this->printProfile('Page');
    $this->runAnonymous(false);
  }

  public function testDeleteNode()
  {
    $this->runAnonymous(true);
    //$this->enableProfiler('Page');

    // delete all relations
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page = $persistenceFacade->load($this->oids['page'], 1);
    $page->deleteNode($persistenceFacade->load($this->oids['document']));
    $page->deleteNode($persistenceFacade->load($this->oids['childPage']), 'ChildPage');
    $page->deleteNode($persistenceFacade->load($this->oids['parentPage']), 'ParentPage');
    $page->deleteNode($persistenceFacade->load($this->oids['author']));
    $page->deleteNode($persistenceFacade->load($this->oids['titleImage']), 'TitleImage');
    $page->deleteNode($persistenceFacade->load($this->oids['normalImage']), 'NormalImage');
    $transaction->commit();

    // test
    $transaction->begin();
    $page = $persistenceFacade->load($this->oids['page'], 1);
    $this->assertEquals(0, sizeof($page->getChildrenEx(null, 'Document', null)));
    $this->assertEquals(0, sizeof($page->getChildrenEx(null, 'ChildPage', null)));
    $this->assertEquals(0, sizeof($page->getParentEx(null, 'ParentPage', null)));
    $this->assertEquals(0, sizeof($page->getParentEx(null, 'Author', null)));
    $this->assertEquals(0, sizeof($page->getChildrenEx(null, 'TitleImage', null)));
    $this->assertEquals(0, sizeof($page->getChildrenEx(null, 'NormalImage', null)));

    $document = $persistenceFacade->load($this->oids['document'], 1);
    $this->assertEquals(0, sizeof($document->getChildrenEx(null, 'Page', null)));

    $childPage = $persistenceFacade->load($this->oids['childPage'], 1);
    $this->assertEquals(0, sizeof($childPage->getParentEx(null, 'ParentPage', null)));

    $parentPage = $persistenceFacade->load($this->oids['parentPage'], 1);
    $this->assertEquals(0, sizeof($parentPage->getChildrenEx(null, 'ChildPage', null)));

    $author = $persistenceFacade->load($this->oids['author'], 1);
    $this->assertEquals(0, sizeof($author->getChildrenEx(null, 'Page', null)));

    $titleImage = $persistenceFacade->load($this->oids['titleImage'], 1);
    $this->assertEquals(0, sizeof($titleImage->getParentEx(null, 'TitlePage', null)));

    $normalImage = $persistenceFacade->load($this->oids['normalImage'], 1);
    $this->assertEquals(0, sizeof($normalImage->getParentEx(null, 'NormalPage', null)));
    $transaction->rollback();

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

    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page = $persistenceFacade->load($this->oids['page'], 1);
    $page->delete();
    $transaction->commit();

    // test
    $transaction->begin();
    $this->assertEquals(null, $persistenceFacade->load($this->oids['page']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['document']));
    $this->assertEquals(null, $persistenceFacade->load($this->oids['childPage']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['parentPage']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['author']));
    $this->assertEquals(null, $persistenceFacade->load($this->oids['titleImage']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['normalImage']));
    $transaction->rollback();

    $this->runAnonymous(false);
  }
}
?>