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

use test\lib\ArrayDataSet;
use test\lib\DatabaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeIterator;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\model\PersistentIterator;
use wcmf\lib\persistence\ObjectId;

/**
 * IteratorTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class IteratorTest extends DatabaseTestCase {

  private $_authorOid = 'Author:202';
  private $_publisherOid = 'Publisher:200';
  private $_bookOid = 'Book:203';

  private $_expectedResultsAuthor = array(
    array("app.src.model.Author:202", 0),
    array("app.src.model.Chapter:303", 1),
    array("app.src.model.Chapter:300", 2),
    array("app.src.model.Chapter:302", 3),
    array("app.src.model.Image:305", 3),
    array("app.src.model.Image:306", 3),
    array("app.src.model.Publisher:200", 1),
    array("app.src.model.Book:203", 2),
  );

  private $_expectedResultsPublisher = array(
    array("app.src.model.Publisher:200", 0),
    array("app.src.model.Book:203", 1),
    array("app.src.model.Chapter:300", 2),
    array("app.src.model.Chapter:302", 3),
    array("app.src.model.Image:305", 3),
    array("app.src.model.Image:306", 3),
    array("app.src.model.Author:202", 1),
    array("app.src.model.Chapter:303", 2),
  );

  private $_expectedResultsBook = array(
    array("app.src.model.Book:203", 0),
    array("app.src.model.Chapter:300", 1),
    array("app.src.model.Chapter:302", 2),
    array("app.src.model.Image:305", 2),
    array("app.src.model.Image:306", 2),
  );

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array('id' => 1),
      ),
      'Publisher' => array(
        array('id' => 200),
      ),
      'NMPublisherAuthor' => array(
        array('id' => 201, 'fk_publisher_id' => 200, 'fk_author_id' => 202),
      ),
      'Author' => array(
        array('id' => 202),
      ),
      'Book' => array(
        array('id' => 203, 'fk_publisher_id' => 200),
      ),
      'Chapter' => array(
        array('id' => 300, 'fk_chapter_id' => 303, 'fk_author_id' => null, 'fk_book_id' => 203),
        array('id' => 302, 'fk_chapter_id' => 300, 'fk_author_id' => null, 'fk_book_id' => null),
        array('id' => 303, 'fk_chapter_id' => null, 'fk_author_id' => 202, 'fk_book_id' => null),
      ),
      'Image' => array(
        array('id' => 305, 'fk_titlechapter_id' => 300, 'fk_chapter_id' => null),
        array('id' => 306, 'fk_titlechapter_id' => null, 'fk_chapter_id' => 300),
      ),
    ));
  }

  public function testPersistentIterater() {
    TestUtil::runAnonymous(true);

    $iterator1 = new PersistentIterator(ObjectId::parse($this->_authorOid));
    $count1 = 0;
    foreach($iterator1 as $depth => $oid) {
      $expectedResult = $this->_expectedResultsAuthor[$count1];
      $this->assertEquals($expectedResult[0], $oid);
      $this->assertEquals($expectedResult[1], $depth);
      $count1++;
    }

    $iterator2 = new PersistentIterator(ObjectId::parse($this->_publisherOid));
    $count2 = 0;
    foreach($iterator2 as $depth => $oid) {
      $expectedResult = $this->_expectedResultsPublisher[$count2];
      $this->assertEquals($expectedResult[0], $oid);
      $this->assertEquals($expectedResult[1], $depth);
      $count2++;
    }

    $iterator3 = new PersistentIterator(ObjectId::parse($this->_bookOid));
    $count3 = 0;
    foreach($iterator3 as $depth => $oid) {
      $expectedResult = $this->_expectedResultsBook[$count3];
      $this->assertEquals($expectedResult[0], $oid);
      $this->assertEquals($expectedResult[1], $depth);
      $count3++;
    }

    TestUtil::runAnonymous(false);
  }

  public function testNodeIterater() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $iterator1 = new NodeIterator($persistenceFacade->load(ObjectId::parse($this->_authorOid)));
    $count1 = 0;
    foreach($iterator1 as $oidStr => $obj) {
      $expectedResult = $this->_expectedResultsAuthor[$count1];
      $this->assertEquals($expectedResult[0], $oidStr);
      $count1++;
    }

    $iterator2 = new NodeIterator($persistenceFacade->load(ObjectId::parse($this->_publisherOid)));
    $count2 = 0;
    foreach($iterator2 as $oidStr => $obj) {
      $expectedResult = $this->_expectedResultsPublisher[$count2];
      $this->assertEquals($expectedResult[0], $oidStr);
      $count2++;
    }

    $iterator3 = new NodeIterator($persistenceFacade->load(ObjectId::parse($this->_bookOid)));
    $count3 = 0;
    foreach($iterator3 as $oidStr => $obj) {
      $expectedResult = $this->_expectedResultsBook[$count3];
      $this->assertEquals($expectedResult[0], $oidStr);
      $count3++;
    }

    TestUtil::runAnonymous(false);
  }

  public function testNodeIteraterReferences() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $node = $persistenceFacade->load(ObjectId::parse($this->_publisherOid));
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

    $node = $persistenceFacade->load(ObjectId::parse($this->_publisherOid));
    $valueIter = new NodeValueIterator($node, true);
    $count = 0;
    for($valueIter->rewind(); $valueIter->valid(); $valueIter->next()) {
      $curIterNode = $valueIter->currentNode();
      $this->assertEquals($this->_publisherOid, $curIterNode->getOID()->__toString());
      $this->assertEquals($curIterNode->getValue($valueIter->key()), $valueIter->current());
      $count++;
    }
    $this->assertEquals(12, $count, "The node has 12 attributes");

    TestUtil::runAnonymous(false);
  }
}
?>