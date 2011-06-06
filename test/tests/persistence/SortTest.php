<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class SortTest extends PHPUnit_Framework_TestCase
{
  private $_pageOidStr = 'Page:12345';
  private $_documentOidStr = 'Document:12345';

  protected function setUp()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page = TestUtil::createTestObject(ObjectId::parse($this->_pageOidStr), array());
    $document = TestUtil::createTestObject(ObjectId::parse($this->_documentOidStr), array());
    $page->addNode($document);
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown()
  {
    TestUtil::runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_pageOidStr));
    TestUtil::deleteTestObject(ObjectId::parse($this->_documentOidStr));
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

    $documentMapper = $persistenceFacade->getMapper('Document');
    $defaultPageOrder = $documentMapper->getDefaultOrder('Page');
    $this->assertEquals('sortkey_page', $defaultPageOrder['sortFieldName']);

    TestUtil::runAnonymous(false);
  }
}
?>