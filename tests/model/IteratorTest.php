<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeSerializer.php");

class IteratorTest extends WCMFTestCase
{
  public function testNodeIterater()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $node = $persistenceFacade->load(new ObjectId('Page', array(10)), BUILDDEPTH_SINGLE);
    echo($node->__toString()."\n");
    $nodeIter = new NodeIterator($node, true);
    $count = 0;
    while (!$nodeIter->isEnd())
    {
      $curIterNode = $nodeIter->getCurrentNode();
      echo($curIterNode->getOID()."\n");
      var_dump(NodeSerializer::serializeNode($curIterNode));
      $count++;
      $nodeIter->proceed();            
    }
    
    $this->assertTrue($count == 1, "");
    
    $this->runAnonymous(false);
  }
  public function testValueIterater()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $node = $persistenceFacade->load(new ObjectId('Page', array(10)), BUILDDEPTH_SINGLE);
    echo($node->__toString()."\n");
    $valueIter = new NodeValueIterator($node, true);
    $count = 0;
    $allNames = '';
    while (!$valueIter->isEnd())
    {
      $curIterNode = $valueIter->getCurrentNode();
      $valueName = $valueIter->getCurrentAttribute();
      $value = $curIterNode->getValue($valueName);
      echo($curIterNode->getOID().":".$valueName."=".$value."\n");
      $allNames += $valueName;
      $count++;
      $valueIter->proceed();            
    }
    
    $this->assertTrue($count == 9, "The node has 9 attributes");
    
    $this->runAnonymous(false);
  }
}
?>