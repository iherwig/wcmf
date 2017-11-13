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
namespace wcmf\test\tests\persistence;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * PersistentFacadeTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentFacadeTest extends DatabaseTestCase {

  private $numChapters = 100;

  protected function getDataSet() {
    $chapters = [];
    for ($i=0; $i<$this->numChapters; $i++) {
      $chapters[] = ['id' => $i, 'sortkey' => $i];
    }

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
      'Chapter' => $chapters,
      // mixed paging
      'Publisher' => [
        ['id' => 1, 'created' => '2016-07-14 12:00:00'], // 1.
        ['id' => 2, 'created' => '2016-07-14 12:00:01'], // 2.
        ['id' => 3, 'created' => '2016-07-14 12:00:02'], // 3.
        ['id' => 4, 'created' => '2016-07-14 12:00:05'], // 6.
        ['id' => 5, 'created' => '2016-07-14 12:00:07'], // 8.
        ['id' => 6, 'created' => '2016-07-14 12:00:08'], // 9.
        ['id' => 7, 'created' => '2016-07-14 12:00:13'], // 14.
        ['id' => 8, 'created' => '2016-07-14 12:00:14'], // 15.
      ],
      'NMPublisherAuthor' => [
        ['id' => 201, 'fk_publisher_id' => 1, 'fk_author_id' => 1],
        ['id' => 202, 'fk_publisher_id' => 2, 'fk_author_id' => 1],
      ],
      'Author' => [
        ['id' => 1, 'created' => '2016-07-14 12:00:03'], // 4.
        ['id' => 2, 'created' => '2016-07-14 12:00:04'], // 5.
        ['id' => 3, 'created' => '2016-07-14 12:00:06'], // 7.
        ['id' => 4, 'created' => '2016-07-14 12:00:09'], // 10.
        ['id' => 5, 'created' => '2016-07-14 12:00:10'], // 11.
        ['id' => 6, 'created' => '2016-07-14 12:00:11'], // 12.
        ['id' => 7, 'created' => '2016-07-14 12:00:12'], // 13.
        ['id' => 8, 'created' => '2016-07-14 12:00:15'], // 16.
      ]
    ]);
  }

  public function testPagingSimple() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // lower bound 1
    $pagingInfo1 = new PagingInfo(0);
    $chapters1 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo1);
    $this->assertEquals(0, sizeof($chapters1));

    // lower bound 2
    $pagingInfo2 = new PagingInfo(1);
    $chapters2 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo2);
    $this->assertEquals(1, sizeof($chapters2));

    // simple
    $pagingInfo3 = new PagingInfo(10);
    $chapters3 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo3);
    $this->assertEquals(10, sizeof($chapters3));

    // out of bounds 1
    $pagingInfo4 = new PagingInfo(-1);
    $chapters4 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo4);
    $this->assertEquals($this->numChapters, sizeof($chapters4));

    // out of bounds 2
    $pagingInfo5 = new PagingInfo(100000000);
    $chapters5 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo5);
    $this->assertEquals($this->numChapters, sizeof($chapters5));

    TestUtil::endSession();
  }

  public function testPagingMultipleTypesAllPages() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = ['Publisher', 'Author'];
    $pagingInfo = new PagingInfo(5);

    // 1. page
    $pagingInfo->setPage(1);
    $objects1 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects1));
    $this->assertEquals('app.src.model.Publisher:1', $objects1[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects1[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects1[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects1[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects1[4]->getOID()->__toString());

    // 2. page
    $pagingInfo->setPage(2);
    $objects2 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects2));
    $this->assertEquals('app.src.model.Publisher:4', $objects2[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:3', $objects2[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects2[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:6', $objects2[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:4', $objects2[4]->getOID()->__toString());

    // 3. page
    $pagingInfo->setPage(3);
    $objects3 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects3));
    $this->assertEquals('app.src.model.Author:5', $objects3[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:6', $objects3[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:7', $objects3[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:7', $objects3[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:8', $objects3[4]->getOID()->__toString());

    // 4. page
    $pagingInfo->setPage(4);
    $objects4 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(1, sizeof($objects4));
    $this->assertEquals('app.src.model.Author:8', $objects4[0]->getOID()->__toString());

    TestUtil::endSession();
  }

  public function testPagingMultipleTypesAllPagesDesc() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = ['Publisher', 'Author'];
    $pagingInfo = new PagingInfo(5);

    // 1. page
    $pagingInfo->setPage(1);
    $objects1 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created DESC'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects1));
    $this->assertEquals('app.src.model.Author:8', $objects1[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:8', $objects1[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:7', $objects1[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:7', $objects1[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:6', $objects1[4]->getOID()->__toString());

    // 2. page
    $pagingInfo->setPage(2);
    $objects2 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created DESC'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects2));
    $this->assertEquals('app.src.model.Author:5', $objects2[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:4', $objects2[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:6', $objects2[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects2[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:3', $objects2[4]->getOID()->__toString());

    // 3. page
    $pagingInfo->setPage(3);
    $objects3 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created DESC'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects3));
    $this->assertEquals('app.src.model.Publisher:4', $objects3[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects3[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects3[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects3[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects3[4]->getOID()->__toString());

    // 4. page
    $pagingInfo->setPage(4);
    $objects4 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created DESC'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(1, sizeof($objects4));
    $this->assertEquals('app.src.model.Publisher:1', $objects4[0]->getOID()->__toString());

    TestUtil::endSession();
  }

  public function testPagingMultipleTypesNthPage() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = ['Publisher', 'Author'];
    $pagingInfo = new PagingInfo(5);

    // 3. page
    $pagingInfo->setPage(3);
    $objects3 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects3));
    $this->assertEquals('app.src.model.Author:5', $objects3[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:6', $objects3[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:7', $objects3[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:7', $objects3[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:8', $objects3[4]->getOID()->__toString());

    // 2. page
    $pagingInfo->setPage(2);
    $objects2 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects2));
    $this->assertEquals('app.src.model.Publisher:4', $objects2[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:3', $objects2[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects2[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:6', $objects2[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:4', $objects2[4]->getOID()->__toString());

    TestUtil::endSession();
  }

  public function testPaging1stPage() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = ['Publisher', 'Author'];
    $pagingInfo = new PagingInfo(5);

    // 1. page
    $objects1 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects1));
    $this->assertEquals('app.src.model.Publisher:1', $objects1[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects1[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects1[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects1[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects1[4]->getOID()->__toString());

    // 1. page
    $pagingInfo->setPage(1);
    $objects2 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects2));
    $this->assertEquals('app.src.model.Publisher:1', $objects2[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects2[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects2[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects2[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects2[4]->getOID()->__toString());

    TestUtil::endSession();
  }

  public function testPagingDifferentPageSizes() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = ['Publisher', 'Author'];

    // page sizes
    $pagingInfo1 = new PagingInfo(5);
    $pagingInfo2 = new PagingInfo(4);
    $pagingInfo3 = new PagingInfo(3);

    // 1. page (5)
    $pagingInfo1->setPage(1);
    $objects1 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo1);
    $this->assertEquals(16, $pagingInfo1->getTotalCount());
    $this->assertEquals(5, sizeof($objects1));
    $this->assertEquals('app.src.model.Publisher:1', $objects1[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects1[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects1[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects1[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects1[4]->getOID()->__toString());

    // 1. page (4)
    $pagingInfo2->setPage(1);
    $objects3 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo2);
    $this->assertEquals(16, $pagingInfo2->getTotalCount());
    $this->assertEquals(4, sizeof($objects3));
    $this->assertEquals('app.src.model.Publisher:1', $objects3[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects3[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects3[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects3[3]->getOID()->__toString());

    // 1. page (3)
    $pagingInfo3->setPage(1);
    $objects5 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo3);
    $this->assertEquals(16, $pagingInfo3->getTotalCount());
    $this->assertEquals(3, sizeof($objects5));
    $this->assertEquals('app.src.model.Publisher:1', $objects5[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects5[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects5[2]->getOID()->__toString());

    // 2. page (5)
    $pagingInfo1->setPage(2);
    $objects2 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo1);
    $this->assertEquals(16, $pagingInfo1->getTotalCount());
    $this->assertEquals(5, sizeof($objects2));
    $this->assertEquals('app.src.model.Publisher:4', $objects2[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:3', $objects2[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects2[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:6', $objects2[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:4', $objects2[4]->getOID()->__toString());

    // 2. page (4)
    $pagingInfo2->setPage(2);
    $objects4 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo2);
    $this->assertEquals(16, $pagingInfo2->getTotalCount());
    $this->assertEquals(4, sizeof($objects4));
    $this->assertEquals('app.src.model.Author:2', $objects4[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:4', $objects4[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:3', $objects4[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects4[3]->getOID()->__toString());

    // 2. page (3)
    $pagingInfo3->setPage(2);
    $objects6 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo3);
    $this->assertEquals(16, $pagingInfo3->getTotalCount());
    $this->assertEquals(3, sizeof($objects6));
    $this->assertEquals('app.src.model.Author:1', $objects6[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects6[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:4', $objects6[2]->getOID()->__toString());

    // 3. page (3)
    $pagingInfo3->setPage(3);
    $objects7 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo3);
    $this->assertEquals(16, $pagingInfo3->getTotalCount());
    $this->assertEquals(3, sizeof($objects7));
    $this->assertEquals('app.src.model.Author:3', $objects7[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects7[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:6', $objects7[2]->getOID()->__toString());

    // 1. page (5)
    $pagingInfo1->setPage(1);
    $objects1 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo1);
    $this->assertEquals(16, $pagingInfo1->getTotalCount());
    $this->assertEquals(5, sizeof($objects1));
    $this->assertEquals('app.src.model.Publisher:1', $objects1[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects1[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects1[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects1[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects1[4]->getOID()->__toString());

    // 2. page (5)
    $pagingInfo1->setPage(2);
    $objects2 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo1);
    $this->assertEquals(16, $pagingInfo1->getTotalCount());
    $this->assertEquals(5, sizeof($objects2));
    $this->assertEquals('app.src.model.Publisher:4', $objects2[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:3', $objects2[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects2[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:6', $objects2[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:4', $objects2[4]->getOID()->__toString());

    // 1. page (4)
    $pagingInfo2->setPage(1);
    $objects3 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo2);
    $this->assertEquals(16, $pagingInfo2->getTotalCount());
    $this->assertEquals(4, sizeof($objects3));
    $this->assertEquals('app.src.model.Publisher:1', $objects3[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects3[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects3[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:1', $objects3[3]->getOID()->__toString());

    // 2. page (4)
    $pagingInfo2->setPage(2);
    $objects4 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo2);
    $this->assertEquals(16, $pagingInfo2->getTotalCount());
    $this->assertEquals(4, sizeof($objects4));
    $this->assertEquals('app.src.model.Author:2', $objects4[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:4', $objects4[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:3', $objects4[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects4[3]->getOID()->__toString());

    // 1. page (3)
    $pagingInfo3->setPage(1);
    $objects5 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo3);
    $this->assertEquals(16, $pagingInfo3->getTotalCount());
    $this->assertEquals(3, sizeof($objects5));
    $this->assertEquals('app.src.model.Publisher:1', $objects5[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects5[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects5[2]->getOID()->__toString());

    // 2. page (3)
    $pagingInfo3->setPage(2);
    $objects6 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo3);
    $this->assertEquals(16, $pagingInfo3->getTotalCount());
    $this->assertEquals(3, sizeof($objects6));
    $this->assertEquals('app.src.model.Author:1', $objects6[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:2', $objects6[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:4', $objects6[2]->getOID()->__toString());

    // 3. page (3)
    $pagingInfo3->setPage(3);
    $objects7 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, ['created'], $pagingInfo3);
    $this->assertEquals(16, $pagingInfo3->getTotalCount());
    $this->assertEquals(3, sizeof($objects7));
    $this->assertEquals('app.src.model.Author:3', $objects7[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects7[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:6', $objects7[2]->getOID()->__toString());

    TestUtil::endSession();
  }

  public function testPagingMultipleTypesCriteria() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = ['Publisher', 'Author'];
    $pagingInfo = new PagingInfo(5);

    $criteria = [new Criteria('Publisher', 'created', 'LIKE', '%0'),
        new Criteria('Author', 'created', 'LIKE', '%0')];

    // 1. page (created date only )
    $pagingInfo->setPage(1);
    $objects1 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, $criteria, ['created'], $pagingInfo);
    $this->assertEquals(2, $pagingInfo->getTotalCount());
    $this->assertEquals(2, sizeof($objects1));
    $this->assertEquals('app.src.model.Publisher:1', $objects1[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Author:5', $objects1[1]->getOID()->__toString());

    TestUtil::endSession();
  }

  public function testPagingMultipleNoOrder() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = ['Publisher', 'Author'];
    $pagingInfo = new PagingInfo(5);

    // 1. page
    $pagingInfo->setPage(1);
    $objects1 = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE, null, null, $pagingInfo);
    $this->assertEquals(16, $pagingInfo->getTotalCount());
    $this->assertEquals(5, sizeof($objects1));
    $this->assertEquals('app.src.model.Publisher:1', $objects1[0]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:2', $objects1[1]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:3', $objects1[2]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:4', $objects1[3]->getOID()->__toString());
    $this->assertEquals('app.src.model.Publisher:5', $objects1[4]->getOID()->__toString());

    TestUtil::endSession();
  }

  public function testManyToManyWithBuildDepth() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $criteria = [new Criteria('Publisher', 'id', '<', 3)];

    $publishers = $persistenceFacade->loadObjects('Publisher', 1, $criteria);
    $this->assertEquals(2, sizeof($publishers));
    $this->assertEquals(1, sizeof($publishers[0]->getValue('Author')));
    $this->assertEquals(1, sizeof($publishers[1]->getValue('Author')));

    TestUtil::endSession();
  }
}
?>