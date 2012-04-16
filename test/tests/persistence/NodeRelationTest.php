<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace test\tests\persistence;

use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * NodeRelationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeRelationTest extends \PHPUnit_Framework_TestCase {

  private $oids = array();
  private $objects = array();

  protected function setUp() {
    TestUtil::runAnonymous(true);

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
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
      $this->objects[$name] = TestUtil::createTestObject($oid, array());
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

    TestUtil::runAnonymous(false);
  }

  protected function tearDown() {
    TestUtil::runAnonymous(true);
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    foreach ($this->oids as $name => $oid) {
      TestUtil::deleteTestObject($oid);
    }
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testAddNode() {
    TestUtil::runAnonymous(true);
    //$this->enableProfiler('Page');

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
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
    TestUtil::runAnonymous(false);
  }

  public function testDeleteNode() {
    TestUtil::runAnonymous(true);
    //$this->enableProfiler('Page');

    // delete all relations
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
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
    $this->assertEquals(0, sizeof($page->getParentsEx(null, 'ParentPage', null)));
    $this->assertEquals(0, sizeof($page->getParentsEx(null, 'Author', null)));
    $this->assertEquals(0, sizeof($page->getChildrenEx(null, 'TitleImage', null)));
    $this->assertEquals(0, sizeof($page->getChildrenEx(null, 'NormalImage', null)));

    $document = $persistenceFacade->load($this->oids['document'], 1);
    $this->assertEquals(0, sizeof($document->getChildrenEx(null, 'Page', null)));

    $childPage = $persistenceFacade->load($this->oids['childPage'], 1);
    $this->assertEquals(0, sizeof($childPage->getParentsEx(null, 'ParentPage', null)));

    $parentPage = $persistenceFacade->load($this->oids['parentPage'], 1);
    $this->assertEquals(0, sizeof($parentPage->getChildrenEx(null, 'ChildPage', null)));

    $author = $persistenceFacade->load($this->oids['author'], 1);
    $this->assertEquals(0, sizeof($author->getChildrenEx(null, 'Page', null)));

    $titleImage = $persistenceFacade->load($this->oids['titleImage'], 1);
    $this->assertEquals(0, sizeof($titleImage->getParentsEx(null, 'TitlePage', null)));

    $normalImage = $persistenceFacade->load($this->oids['normalImage'], 1);
    $this->assertEquals(0, sizeof($normalImage->getParentsEx(null, 'NormalPage', null)));
    $transaction->rollback();

    //$this->printProfile('Page');
    TestUtil::runAnonymous(false);
  }

  public function _testLoadSingle() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $page = $persistenceFacade->load($this->oids['page'], BuildDepth::SINGLE);
    $page->loadChildren('Document', 1);
    $document = $page->getFirstChild('Document');
    echo "title: ".$document->getTitle()."\n";
    //*
    foreach($page->getValueNames() as $name) {
      $value = $page->getValue($name);
      echo $name.": ".$value."(".sizeof($value).")\n";
    }
    //*/
    TestUtil::runAnonymous(false);
  }

  public function testDelete() {
    TestUtil::runAnonymous(true);

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
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

    TestUtil::runAnonymous(false);
  }

  public function testNavigabilityManyToMany() {
    TestUtil::runAnonymous(true);

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $nmPageDocument = $persistenceFacade->loadFirstObject("NMPageDocument");
    // the Document is navigable from the NMPageDocument instance
    $document = $nmPageDocument->getValue("Document");
    $this->assertNotNull($document);
    $transaction->rollback();

    $transaction->begin();
    $document = $persistenceFacade->load($document->getOID());
    // the NMPageDocument is not navigable from the Document instance
    $nmPageDocuments = $document->getValue("NMPageDocument");
    $this->assertNull($nmPageDocuments);
    // but the Page is
    $pages = $document->getValue("Page");
    $this->assertNotNull($pages);
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }
}
?>