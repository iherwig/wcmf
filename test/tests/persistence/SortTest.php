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
use wcmf\lib\model\NodeSortkeyComparator;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * SortTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SortTest extends \PHPUnit_Framework_TestCase {

  private $_pageOidStr = 'Page:12345';
  private $_documentOid1Str = 'Document:123451';
  private $_documentOid2Str = 'Document:123452';
  private $_documentOid3Str = 'Document:123453';
  private $_documentOidStrs = array();
  private $_pageOid1Str = 'Page:123454';
  private $_pageOid2Str = 'Page:123455';
  private $_pageOid3Str = 'Page:123456';
  private $_pageOidStrs = array();

  protected function setUp() {
    $this->_documentOidStrs = array($this->_documentOid1Str, $this->_documentOid2Str, $this->_documentOid3Str);
    $this->_pageOidStrs = array($this->_pageOid1Str, $this->_pageOid2Str, $this->_pageOid3Str);
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $mainPage = TestUtil::createTestObject(ObjectId::parse($this->_pageOidStr), array());
    for ($i=0, $count=sizeof($this->_documentOidStrs); $i<$count; $i++) {
      $document = TestUtil::createTestObject(ObjectId::parse($this->_documentOidStrs[$i]), array());
      $mainPage->addNode($document);
    }
    for ($i=0, $count=sizeof($this->_pageOidStrs); $i<$count; $i++) {
      $page = TestUtil::createTestObject(ObjectId::parse($this->_pageOidStrs[$i]), array());
      $mainPage->addNode($page, "ChildPage");
    }
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown() {
    TestUtil::runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_pageOidStr));
    for ($i=0, $count=sizeof($this->_documentOidStrs); $i<$count; $i++) {
      TestUtil::deleteTestObject(ObjectId::parse($this->_documentOidStrs[$i]));
    }
    for ($i=0, $count=sizeof($this->_pageOidStrs); $i<$count; $i++) {
      TestUtil::deleteTestObject(ObjectId::parse($this->_pageOidStrs[$i]));
    }
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testDefaultOrder() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $documentMapper = $persistenceFacade->getMapper('Document');
    $defaultPageOrder = $documentMapper->getDefaultOrder('Page');
    $this->assertEquals('sortkey_page', $defaultPageOrder['sortFieldName']);

    TestUtil::runAnonymous(false);
  }

  public function testImplicitOrderUpdateSimple() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    // get the existing order
    $page = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr));
    $pages = $page->getValue("ChildPage");
    $pageOids = array();
    for ($i=0, $count=sizeof($pages); $i<$count; $i++) {
      $pageOids[] = $pages[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastPage = array_pop($pages);
    array_unshift($pages, $lastPage);
    $page->setNodeOrder($pages);
    $transaction->commit();

    // reload
    $transaction->begin();
    $page = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), 1);
    $pages = $page->getChildrenEx(null, "ChildPage");
    $this->assertEquals($pageOids[0], $pages[1]->getOID()->__toString());
    $this->assertEquals($pageOids[1], $pages[2]->getOID()->__toString());
    $this->assertEquals($pageOids[2], $pages[0]->getOID()->__toString());
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testImplicitOrderUpdateManyToMany() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    // get the existing order
    $page = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr));
    $documents = $page->getValue("Document");
    $documentOids = array();
    for ($i=0, $count=sizeof($documents); $i<$count; $i++) {
      $documentOids[] = $documents[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastDocument = array_pop($documents);
    array_unshift($documents, $lastDocument);
    $page->setNodeOrder($documents);
    $transaction->commit();

    // reload
    $transaction->begin();
    $page = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), 1);
    $documents = $page->getChildrenEx(null, "Document");
    $this->assertEquals($documentOids[0], $documents[1]->getOID()->__toString());
    $this->assertEquals($documentOids[1], $documents[2]->getOID()->__toString());
    $this->assertEquals($documentOids[2], $documents[0]->getOID()->__toString());
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testImplicitOrderUpdateMixedType() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();

    $page = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), 1);
    $children = $page->getChildren();
    // get the existing order
    $childOids = array();
    for ($i=0, $count=sizeof($children); $i<$count; $i++) {
      $childOids[] = $children[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastChild = array_pop($children);
    array_unshift($children, $lastChild);
    $page->setNodeOrder($children);
    $transaction->commit();

    // reload
    $transaction->begin();
    $page = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), 1);
    $children = $page->getChildren();
    $comparator = new NodeSortkeyComparator($page, $children);
    usort($children, array($comparator, 'compare'));
    $this->assertEquals($childOids[sizeof($childOids)-1], $children[0]->getOID()->__toString());
    $this->assertEquals($childOids[0], $children[1]->getOID()->__toString());
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }
}
?>