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
namespace test\tests\model;

use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeIterator;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * IteratorTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class IteratorTest extends \PHPUnit_Framework_TestCase {

  private $_pageOidStr = 'Page:12345';

  protected function setUp() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    TestUtil::createTestObject(ObjectId::parse($this->_pageOidStr), array());
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown() {
    TestUtil::runAnonymous(true);
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_pageOidStr));
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testNodeIterater() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $node = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), BuildDepth::SINGLE);
    $node->setName('original name');
    $nodeIter = new NodeIterator($node);
    $count = 0;
    foreach($nodeIter as $oidStr => $obj) {
      // change a value to check if obj is really a reference
      $obj->setName('modified name');
      $count++;
    }
    $this->assertEquals('modified name', $node->getName());
    $this->assertEquals(1, $count);

    TestUtil::runAnonymous(false);
  }

  public function _testValueIterater() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $node = $persistenceFacade->load(ObjectId::parse($this->_pageOidStr), BuildDepth::SINGLE);
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