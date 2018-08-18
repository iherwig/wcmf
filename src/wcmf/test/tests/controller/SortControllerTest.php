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
namespace wcmf\test\tests\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;

/**
 * SortControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SortControllerTest extends ControllerTestCase {

  protected function getControllerName() {
    return 'wcmf\application\controller\SortController';
  }

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
      'Author' => [
        ['id' => 100],
      ],
      'Chapter' => [
        ['id' => 203, 'fk_author_id' => 100, 'sortkey' => 203.0, 'sortkey_author' => 203.0],
        ['id' => 204, 'fk_author_id' => 100, 'sortkey' => 204.0, 'sortkey_author' => 204.0],
        ['id' => 205, 'fk_author_id' => 100, 'sortkey' => 205.0, 'sortkey_author' => 205.0],
      ],
      'Book' => [
        ['id' => 303],
        ['id' => 304],
        ['id' => 305],
        ['id' => 306],
      ],
      'NMBookBook' => [
        ['id' => 403, 'fk_referencedbook_id' => 304, 'fk_referencingbook_id' => 303, 'sortkey_referencingbook' => 403.0],
        ['id' => 404, 'fk_referencedbook_id' => 305, 'fk_referencingbook_id' => 303, 'sortkey_referencingbook' => 404.0],
        ['id' => 405, 'fk_referencedbook_id' => 306, 'fk_referencingbook_id' => 303, 'sortkey_referencingbook' => 405.0],
      ],
      'Image' => [
        ['id' => 503, 'fk_chapter_id' => 203, 'sortkey' => 205.0, 'sortkey_normalchapter' => 205.0],
        ['id' => 504, 'fk_chapter_id' => 203, 'sortkey' => 204.0, 'sortkey_normalchapter' => 204.0],
        ['id' => 505, 'fk_chapter_id' => 203, 'sortkey' => 203.0, 'sortkey_normalchapter' => 203.0],
      ]
    ]);
  }

  /**
   * @group controller
   */
  public function testAscMoveBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkChapterOrder([203, 204, 205]));

    // simulate a movebefore call
    $data = [
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:203'
    ];
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->checkChapterOrder([205, 203, 204]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testAscMoveBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkChapterOrder([203, 204, 205]));

    // simulate a movebefore call
    $data = [
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:204'
    ];
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->checkChapterOrder([203, 205, 204]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testAscMoveBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkChapterOrder([203, 204, 205]));

    // simulate a movebefore call
    $data = [
      'insertOid' => 'Chapter:203',
      'referenceOid' => 'ORDER_BOTTOM'
    ];
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->checkChapterOrder([204, 205, 203]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testAscInsertBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkChapterOrder([203, 204, 205], 100));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Author:100',
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:203',
      'role' => 'Chapter'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkChapterOrder([205, 203, 204], 100));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testAscInsertBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkChapterOrder([203, 204, 205], 100));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Author:100',
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:204',
      'role' => 'Chapter'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkChapterOrder([203, 205, 204], 100));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testAscInsertBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkChapterOrder([203, 204, 205], 100));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Author:100',
      'insertOid' => 'Chapter:203',
      'referenceOid' => 'ORDER_BOTTOM',
      'role' => 'Chapter'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkChapterOrder([204, 205, 203], 100));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDescMoveBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkImageOrder([503, 504, 505]));

    // simulate a movebefore call
    $data = [
      'insertOid' => 'Image:505',
      'referenceOid' => 'Image:503'
    ];
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->checkImageOrder([505, 503, 504]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDescMoveBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkImageOrder([503, 504, 505]));

    // simulate a movebefore call
    $data = [
      'insertOid' => 'Image:505',
      'referenceOid' => 'Image:504'
    ];
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->checkImageOrder([503, 505, 504]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDescMoveBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkImageOrder([503, 504, 505]));

    // simulate a movebefore call
    $data = [
      'insertOid' => 'Image:503',
      'referenceOid' => 'ORDER_BOTTOM'
    ];
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->checkImageOrder([504, 505, 503]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDescInsertBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkImageOrder([503, 504, 505], 203));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Chapter:203',
      'insertOid' => 'Image:505',
      'referenceOid' => 'Image:503',
      'role' => 'NormalImage'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkImageOrder([505, 503, 504], 203));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDescInsertBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkImageOrder([503, 504, 505], 203));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Chapter:203',
      'insertOid' => 'Image:505',
      'referenceOid' => 'Image:504',
      'role' => 'NormalImage'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkImageOrder([503, 505, 504], 203));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDescInsertBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkImageOrder([503, 504, 505], 203));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Chapter:203',
      'insertOid' => 'Image:503',
      'referenceOid' => 'ORDER_BOTTOM',
      'role' => 'NormalImage'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkImageOrder([504, 505, 503], 203));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testNMInsertBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkNMBookOrder([304, 305, 306], 303));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Book:303',
      'insertOid' => 'Book:306',
      'referenceOid' => 'Book:304',
      'role' => 'ReferencedBook'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkNMBookOrder([306, 304, 305], 303));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testNMInsertBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkNMBookOrder([304, 305, 306], 303));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Book:303',
      'insertOid' => 'Book:306',
      'referenceOid' => 'Book:305',
      'role' => 'ReferencedBook'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkNMBookOrder([304, 306, 305], 303));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testNMInsertBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->checkNMBookOrder([304, 305, 306], 303));

    // simulate a insertbefore call
    $data = [
      'containerOid' => 'Book:303',
      'insertOid' => 'Book:304',
      'referenceOid' => 'ORDER_BOTTOM',
      'role' => 'ReferencedBook'
    ];
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->checkNMBookOrder([305, 306, 304], 303));

    TestUtil::endSession();
  }

  private function checkChapterOrder(array $order, $authorId=null) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($authorId == null) {
      $chapters = $persistenceFacade->loadObjects('Chapter');
    }
    else {
      $author = $persistenceFacade->load(new ObjectId('Author', $authorId));
      $chapters = $author->getValue('Chapter');
    }
    for ($i=0, $count=sizeof($chapters); $i<$count; $i++) {
      if ($chapters[$i]->getOID()->getFirstId() != $order[$i]) {
        return false;
      }
    }
    return true;
  }

  private function checkImageOrder(array $order, $chapterId=null) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($chapterId == null) {
      $images = $persistenceFacade->loadObjects('Image');
    }
    else {
      $chapter = $persistenceFacade->load(new ObjectId('Chapter', $chapterId));
      $images = $chapter->getValue('NormalImage');
    }
    for ($i=0, $count=sizeof($images); $i<$count; $i++) {
      if ($images[$i]->getOID()->getFirstId() != $order[$i]) {
        return false;
      }
    }
    return true;
  }

  private function checkNMBookOrder(array $order, $bookId) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $referencingBook = $persistenceFacade->load(new ObjectId('Book', $bookId));
    $referencedBooks = $referencingBook->getValue('ReferencedBook');
    for ($i=0, $count=sizeof($referencedBooks); $i<$count; $i++) {
      if ($referencedBooks[$i]->getOID()->getFirstId() != $order[$i]) {
        return false;
      }
    }
    return true;
  }
}
?>