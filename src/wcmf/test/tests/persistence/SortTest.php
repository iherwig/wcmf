<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeSortkeyComparator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;

/**
 * SortTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SortTest extends DatabaseTestCase {

  private $_chapterOidStr = 'Chapter:12345';
  private $_publisherOidStr = 'Publisher:12345';

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'config' => ''),
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
      ),
      'Publisher' => array(
        array('id' => 12345),
      ),
      'NMPublisherAuthor' => array(
        array('id' => 123451, 'fk_publisher_id' => 12345, 'fk_author_id' => 123454, 'sortkey_publisher' => 123454),
        array('id' => 123452, 'fk_publisher_id' => 12345, 'fk_author_id' => 123455, 'sortkey_publisher' => 123455),
        array('id' => 123453, 'fk_publisher_id' => 12345, 'fk_author_id' => 123456, 'sortkey_publisher' => 123456),
      ),
      'Author' => array(
        array('id' => 123454),
        array('id' => 123455),
        array('id' => 123456),
      ),
      'Chapter' => array(
        array('id' => 12345, 'fk_chapter_id' => null, 'sortkey_parentchapter' => null, 'sortkey' => null),
        array('id' => 123454, 'fk_chapter_id' => 12345, 'sortkey_parentchapter' => 123454, 'sortkey' => 123454),
        array('id' => 123455, 'fk_chapter_id' => 12345, 'sortkey_parentchapter' => 123455, 'sortkey' => 123455),
        array('id' => 123456, 'fk_chapter_id' => 12345, 'sortkey_parentchapter' => 123456, 'sortkey' => 123456),
      ),
    ));
  }

  public function testDefaultOrder() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $publisherMapper = $persistenceFacade->getMapper('Publisher');
    $defaultAuthorOrder = $publisherMapper->getDefaultOrder('Author');
    $this->assertEquals(1, sizeof($defaultAuthorOrder));
    $this->assertEquals('sortkey_author', $defaultAuthorOrder[0]['sortFieldName']);
    $this->assertEquals('app.src.model.NMPublisherAuthor', $defaultAuthorOrder[0]['sortType']);
    $this->assertEquals(true, $defaultAuthorOrder[0]['isSortkey']);
    $this->assertEquals('ASC', $defaultAuthorOrder[0]['sortDirection']);

    TestUtil::endSession();
  }

  public function testImplicitOrderUpdateSimple() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    // get the existing order
    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->_chapterOidStr));
    $subChapters1 = $chapter1->getValue("SubChapter");
    $chapterOids = array();
    for ($i=0, $count=sizeof($subChapters1); $i<$count; $i++) {
      $chapterOids[] = $subChapters1[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastChapter = array_pop($subChapters1);
    array_unshift($subChapters1, $lastChapter);
    $chapter1->setNodeOrder($subChapters1);
    $transaction->commit();

    // reload
    $transaction->begin();
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->_chapterOidStr), 1);
    $subChapters2 = $chapter2->getChildrenEx(null, "SubChapter");
    $this->assertEquals($chapterOids[0], $subChapters2[1]->getOID()->__toString());
    $this->assertEquals($chapterOids[1], $subChapters2[2]->getOID()->__toString());
    $this->assertEquals($chapterOids[2], $subChapters2[0]->getOID()->__toString());
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testImplicitOrderUpdateManyToMany() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    // get the existing order
    $publisher1 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr));
    $authors1 = $publisher1->getValue("Author");
    $authorOids = array();
    for ($i=0, $count=sizeof($authors1); $i<$count; $i++) {
      $authorOids[] = $authors1[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastAuthor = array_pop($authors1);
    array_unshift($authors1, $lastAuthor);
    $publisher1->setNodeOrder($authors1);
    $transaction->commit();

    // reload
    $transaction->begin();
    $publisher2 = $persistenceFacade->load(ObjectId::parse($this->_publisherOidStr), 1);
    $authors2 = $publisher2->getChildrenEx(null, "Author");
    $this->assertEquals($authorOids[0], $authors2[1]->getOID()->__toString());
    $this->assertEquals($authorOids[1], $authors2[2]->getOID()->__toString());
    $this->assertEquals($authorOids[2], $authors2[0]->getOID()->__toString());
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testImplicitOrderUpdateMixedType() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();

    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->_chapterOidStr), 1);
    $children1 = $chapter1->getChildren();
    // get the existing order
    $childOids = array();
    for ($i=0, $count=sizeof($children1); $i<$count; $i++) {
      $childOids[] = $children1[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastChild = array_pop($children1);
    array_unshift($children1, $lastChild);
    $chapter1->setNodeOrder($children1);
    $transaction->commit();

    // reload
    $transaction->begin();
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->_chapterOidStr), 1);
    $children2 = $chapter2->getChildren();
    $comparator = new NodeSortkeyComparator($chapter2, $children2);
    usort($children2, array($comparator, 'compare'));
    $this->assertEquals($childOids[sizeof($childOids)-1], $children2[0]->getOID()->__toString());
    $this->assertEquals($childOids[0], $children2[1]->getOID()->__toString());
    $transaction->rollback();

    TestUtil::endSession();
  }
}
?>