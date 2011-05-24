<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeValueIterator.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class IteratorTest extends WCMFTestCase
{
  private $_pageOidStr = 'Page:12345';

  protected function setUp()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $this->createTestObject(ObjectId::parse($this->_pageOidStr), array());
    $transaction->commit();
    $this->runAnonymous(false);
  }

  protected function tearDown()
  {
    $this->runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    $this->deleteTestObject(ObjectId::parse($this->_pageOidStr));
    $transaction->commit();
    $this->runAnonymous(false);
  }

  public function testNodeIterater()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $node = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), BUILDDEPTH_SINGLE);
    $node->setName('original name');
    $nodeIter = new NodeIterator($node);
    $count = 0;
    foreach($nodeIter as $oidStr => $obj)
    {
      // change a value to check if obj is really a reference
      $obj->setName('modified name');
      $count++;
    }
    $this->assertEquals('modified name', $node->getName());
    $this->assertEquals(1, $count);

    $this->runAnonymous(false);
  }
  public function _testValueIterater()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $node = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), BUILDDEPTH_SINGLE);
    $valueIter = new NodeValueIterator($node, true);
    $count = 0;
    for($valueIter->rewind(); $valueIter->valid(); $valueIter->next()) {
      $curIterNode = $valueIter->currentNode();
      $this->assertEquals($this->_pageOidStr, $curIterNode->getOID()->__toString());
      $this->assertEquals($curIterNode->getValue($valueIter->key()), $valueIter->current());
      $count++;
    }
    $this->assertEquals(12, $count, "The node has 12 attributes");

    $this->runAnonymous(false);
  }
}
?>