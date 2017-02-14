<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\model;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeIterator;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\model\PersistentIterator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * IteratorTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class IteratorTest extends DatabaseTestCase {

  private $authorOid = 'Author:202';
  private $publisherOid = 'Publisher:200';
  private $bookOid = 'Book:203';

  private $expectedResultsAuthor = array(
    array("app.src.model.Author:202", 0),
    array("app.src.model.Chapter:303", 1),
    array("app.src.model.Chapter:300", 2),
    array("app.src.model.Chapter:302", 3),
    array("app.src.model.Image:305", 3),
    array("app.src.model.Image:306", 3),
    array("app.src.model.Publisher:200", 1),
    array("app.src.model.Book:203", 2),
  );

  private $expectedResultsPublisher = array(
    array("app.src.model.Publisher:200", 0),
    array("app.src.model.Book:203", 1),
    array("app.src.model.Chapter:300", 2),
    array("app.src.model.Chapter:302", 3),
    array("app.src.model.Image:305", 3),
    array("app.src.model.Image:306", 3),
    array("app.src.model.Author:202", 1),
    array("app.src.model.Chapter:303", 2),
  );

  private $expectedResultsBook = array(
    array("app.src.model.Book:203", 0),
    array("app.src.model.Chapter:300", 1),
    array("app.src.model.Chapter:302", 2),
    array("app.src.model.Image:305", 2),
    array("app.src.model.Image:306", 2),
  );

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        ['table' => ''],
      ),
      'User' => array(
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ),
      'NMUserRole' => array(
        ['fk_user_id' => 0, 'fk_role_id' => 0],
      ),
      'Role' => array(
        ['id' => 0, 'name' => 'administrators'],
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
    TestUtil::startSession('admin', 'admin');

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $session = ObjectFactory::getInstance('session');

    $iterator1 = new PersistentIterator('PersistentIterator1', $persistenceFacade, $session,
            ObjectId::parse($this->authorOid));
    $count1 = 0;
    foreach($iterator1 as $depth => $oid) {
      $expectedResult = $this->expectedResultsAuthor[$count1];
      $this->assertEquals($expectedResult[0], $oid);
      $this->assertEquals($expectedResult[1], $depth);
      $count1++;
    }

    $iterator2 = new PersistentIterator('PersistentIterator2', $persistenceFacade, $session,
            ObjectId::parse($this->publisherOid));
    $count2 = 0;
    foreach($iterator2 as $depth => $oid) {
      $expectedResult = $this->expectedResultsPublisher[$count2];
      $this->assertEquals($expectedResult[0], $oid);
      $this->assertEquals($expectedResult[1], $depth);
      $count2++;
    }

    $iterator3 = new PersistentIterator('PersistentIterator3', $persistenceFacade, $session,
            ObjectId::parse($this->bookOid));
    $count3 = 0;
    foreach($iterator3 as $depth => $oid) {
      $expectedResult = $this->expectedResultsBook[$count3];
      $this->assertEquals($expectedResult[0], $oid);
      $this->assertEquals($expectedResult[1], $depth);
      $count3++;
    }

    TestUtil::endSession();
  }

  public function testNodeIterater() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $iterator1 = new NodeIterator($persistenceFacade->load(ObjectId::parse($this->authorOid)));
    $count1 = 0;
    foreach($iterator1 as $oidStr => $obj) {
      $expectedResult = $this->expectedResultsAuthor[$count1];
      $this->assertEquals($expectedResult[0], $oidStr);
      $count1++;
    }

    $iterator2 = new NodeIterator($persistenceFacade->load(ObjectId::parse($this->publisherOid)));
    $count2 = 0;
    foreach($iterator2 as $oidStr => $obj) {
      $expectedResult = $this->expectedResultsPublisher[$count2];
      $this->assertEquals($expectedResult[0], $oidStr);
      $count2++;
    }

    $iterator3 = new NodeIterator($persistenceFacade->load(ObjectId::parse($this->bookOid)));
    $count3 = 0;
    foreach($iterator3 as $oidStr => $obj) {
      $expectedResult = $this->expectedResultsBook[$count3];
      $this->assertEquals($expectedResult[0], $oidStr);
      $count3++;
    }

    TestUtil::endSession();
  }

  public function testNodeIteraterReferences() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $node = $persistenceFacade->load(ObjectId::parse($this->publisherOid));
    $node->setValue('name', 'original name');
    $nodeIter = new NodeIterator($node);
    $count = 0;
    foreach($nodeIter as $oidStr => $obj) {
      // change a value to check if obj is really a reference
      $obj->setValue('name', 'modified name');
      $count++;
    }
    $this->assertEquals('modified name', $node->getValue('name'));
    $this->assertEquals(1, $count);

    TestUtil::endSession();
  }

  public function _testValueIterater() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $node = $persistenceFacade->load(ObjectId::parse($this->publisherOid));
    $valueIter = new NodeValueIterator($node, true);
    $count = 0;
    for($valueIter->rewind(); $valueIter->valid(); $valueIter->next()) {
      $curIterNode = $valueIter->currentNode();
      $this->assertEquals($this->publisherOid, $curIterNode->getOID()->__toString());
      $this->assertEquals($curIterNode->getValue($valueIter->key()), $valueIter->current());
      $count++;
    }
    $this->assertEquals(12, $count, "The node has 12 attributes");

    TestUtil::endSession();
  }
}
?>