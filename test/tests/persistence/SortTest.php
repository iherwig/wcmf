<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class SortTest extends WCMFTestCase
{
  private $_pageOidStr = 'Page:12345';
  private $_documentOidStr = 'Document:12345';

  protected function setUp()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page = $this->createTestObject(ObjectId::parse($this->_pageOidStr), array());
    $document = $this->createTestObject(ObjectId::parse($this->_documentOidStr), array());
    $page->addNode($document);
    $transaction->commit();
    $this->runAnonymous(false);
  }

  protected function tearDown()
  {
    $this->runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    $this->deleteTestObject(ObjectId::parse($this->_pageOidStr));
    $this->deleteTestObject(ObjectId::parse($this->_documentOidStr));
    $transaction->commit();
    $this->runAnonymous(false);
  }

  public function testDefaultOrder()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $documentMapper = $persistenceFacade->getMapper('Document');
    $defaultPageOrder = $documentMapper->getDefaultOrder('Page');
    $this->assertEquals('sortkey_page', $defaultPageOrder['sortFieldName']);

    $this->runAnonymous(false);
  }
}
?>