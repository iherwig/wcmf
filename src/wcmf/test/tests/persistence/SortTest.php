<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
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

  private $chapterOidStr = 'Chapter:12345';
  private $publisherOidStr = 'Publisher:12345';

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ],
      'NMUserRole' => [
        ['fk_user_id' => 0, 'fk_role_id' => 0],
      ],
      'Role' => [
        ['id' => 0, 'name' => 'administrators'],
      ],
      'Publisher' => [
        ['id' => 12345],
      ],
      'NMPublisherAuthor' => [
        ['id' => 123451, 'fk_publisher_id' => 12345, 'fk_author_id' => 123454, 'sortkey_publisher' => 123454],
        ['id' => 123452, 'fk_publisher_id' => 12345, 'fk_author_id' => 123455, 'sortkey_publisher' => 123455],
        ['id' => 123453, 'fk_publisher_id' => 12345, 'fk_author_id' => 123456, 'sortkey_publisher' => 123456],
      ],
      'Author' => [
        ['id' => 123454],
        ['id' => 123455],
        ['id' => 123456],
      ],
      'Chapter' => [
        ['id' => 12345, 'fk_chapter_id' => null, 'sortkey_parentchapter' => null, 'sortkey' => null],
        ['id' => 123454, 'fk_chapter_id' => 12345, 'sortkey_parentchapter' => 123454, 'sortkey' => 123454],
        ['id' => 123455, 'fk_chapter_id' => 12345, 'sortkey_parentchapter' => 123455, 'sortkey' => 123455],
        ['id' => 123456, 'fk_chapter_id' => 12345, 'sortkey_parentchapter' => 123456, 'sortkey' => 123456],
      ],
    ]);
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
    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->chapterOidStr));
    $subChapters1 = $chapter1->getValue("SubChapter");
    $orderedChapters = [];
    $chapterOids = [];
    for ($i=0, $count=sizeof($subChapters1); $i<$count; $i++) {
      $orderedChapters[] = $subChapters1[$i];
      $chapterOids[] = $subChapters1[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastChapter = array_pop($orderedChapters);
    array_unshift($orderedChapters, $lastChapter);
    $chapter1->setNodeOrder($orderedChapters);
    $transaction->commit();

    // reload
    $transaction->begin();
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->chapterOidStr), 1);
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
    $publisher1 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr));
    $authors1 = $publisher1->getValue("Author");
    $orderedAuthors = [];
    $authorOids = [];
    for ($i=0, $count=sizeof($authors1); $i<$count; $i++) {
      $orderedAuthors[] = $authors1[$i];
      $authorOids[] = $authors1[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastAuthor = array_pop($orderedAuthors);
    array_unshift($orderedAuthors, $lastAuthor);
    $publisher1->setNodeOrder($orderedAuthors);
    $transaction->commit();

    // reload
    $transaction->begin();
    $publisher2 = $persistenceFacade->load(ObjectId::parse($this->publisherOidStr), 1);
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
    // get the existing order
    $chapter1 = $persistenceFacade->load(ObjectId::parse($this->chapterOidStr), 1);
    $children1 = $chapter1->getChildren();
    $orderedChildren = [];
    $childOids = [];
    for ($i=0, $count=sizeof($children1); $i<$count; $i++) {
      $orderedChildren[] = $children1[$i];
      $childOids[] = $children1[$i]->getOID()->__toString();
    }
    // put last into first place
    $lastChild = array_pop($orderedChildren);
    array_unshift($orderedChildren, $lastChild);
    $chapter1->setNodeOrder($orderedChildren);
    $transaction->commit();

    // reload
    $transaction->begin();
    $chapter2 = $persistenceFacade->load(ObjectId::parse($this->chapterOidStr), 1);
    $children2 = $chapter2->getChildren();
    $comparator = new NodeSortkeyComparator($chapter2, $children2);
    usort($children2, [$comparator, 'compare']);
    $this->assertEquals($childOids[sizeof($childOids)-1], $children2[0]->getOID()->__toString());
    $this->assertEquals($childOids[0], $children2[1]->getOID()->__toString());
    $transaction->rollback();

    TestUtil::endSession();
  }
}
?>