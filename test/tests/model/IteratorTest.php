<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeValueIterator.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class IteratorTest extends PHPUnit_Framework_TestCase
{
  private $_pageOidStr = 'Page:12345';

  protected function setUp()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    TestUtil::createTestObject(ObjectId::parse($this->_pageOidStr), array());
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown()
  {
    TestUtil::runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_pageOidStr));
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testNodeIterater()
  {
    TestUtil::runAnonymous(true);
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

    TestUtil::runAnonymous(false);
  }
  public function _testValueIterater()
  {
    TestUtil::runAnonymous(true);
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

    TestUtil::runAnonymous(false);
  }
}
?>