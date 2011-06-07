<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class SortTest extends PHPUnit_Framework_TestCase
{
  private $_pageOidStr = 'Page:12345';
  private $_documentOid1Str = 'Document:123451';
  private $_documentOid2Str = 'Document:123452';
  private $_documentOid3Str = 'Document:123453';
  private $_documentOidStrs = array();

  protected function setUp()
  {
    $this->_documentOidStrs = array($this->_documentOid1Str, $this->_documentOid2Str, $this->_documentOid3Str);
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page = TestUtil::createTestObject(ObjectId::parse($this->_pageOidStr), array());
    for ($i=0, $count=sizeof($this->_documentOidStrs); $i<$count; $i++) {
      $document = TestUtil::createTestObject(ObjectId::parse($this->_documentOidStrs[$i]), array());
      $page->addNode($document);
    }
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown()
  {
    TestUtil::runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_pageOidStr));
    for ($i=0, $count=sizeof($this->_documentOidStrs); $i<$count; $i++) {
      TestUtil::deleteTestObject(ObjectId::parse($this->_documentOidStrs[$i]));
    }
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testDefaultOrder()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $documentMapper = $persistenceFacade->getMapper('Document');
    $defaultPageOrder = $documentMapper->getDefaultOrder('Page');
    $this->assertEquals('sortkey_page', $defaultPageOrder['sortFieldName']);

    TestUtil::runAnonymous(false);
  }

  public function testImplicitOrderUpdate()
  {
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
    $page->setValue("Document", $documents);
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
}
?>