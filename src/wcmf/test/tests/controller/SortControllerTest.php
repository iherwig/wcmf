<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
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
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
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
      'Author' => array(
        array('id' => 100),
      ),
      'Chapter' => array(
        array('id' => 203, 'fk_author_id' => 100, 'sortkey' => 203.0, 'sortkey_author' => 203.0),
        array('id' => 204, 'fk_author_id' => 100, 'sortkey' => 204.0, 'sortkey_author' => 204.0),
        array('id' => 205, 'fk_author_id' => 100, 'sortkey' => 205.0, 'sortkey_author' => 205.0),
      ),
      'Book' => array(
        array('id' => 303),
        array('id' => 304),
        array('id' => 305),
        array('id' => 306),
      ),
      'NMBookBook' => array(
        array('id' => 403, 'fk_referencedbook_id' => 304, 'fk_referencingbook_id' => 303, 'sortkey_referencingbook' => 403.0),
        array('id' => 404, 'fk_referencedbook_id' => 305, 'fk_referencingbook_id' => 303, 'sortkey_referencingbook' => 404.0),
        array('id' => 405, 'fk_referencedbook_id' => 306, 'fk_referencingbook_id' => 303, 'sortkey_referencingbook' => 405.0),
      )
    ));
  }

  /**
   * @group controller
   */
  public function testMoveBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testChapterOrder([203, 204, 205]));

    // simulate a movebefore call
    $data = array(
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:203'
    );
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->testChapterOrder([205, 203, 204]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testMoveBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testChapterOrder([203, 204, 205]));

    // simulate a movebefore call
    $data = array(
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:204'
    );
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->testChapterOrder([203, 205, 204]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testMoveBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testChapterOrder([203, 204, 205]));

    // simulate a movebefore call
    $data = array(
      'insertOid' => 'Chapter:203',
      'referenceOid' => 'ORDER_BOTTOM'
    );
    $this->runRequest('moveBefore', $data);

    // test
    $this->assertTrue($this->testChapterOrder([204, 205, 203]));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testInsertBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testChapterOrder([203, 204, 205], 100));

    // simulate a insertbefore call
    $data = array(
      'containerOid' => 'Author:100',
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:203',
      'role' => 'Chapter'
    );
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->testChapterOrder([205, 203, 204], 100));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testInsertBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testChapterOrder([203, 204, 205], 100));

    // simulate a insertbefore call
    $data = array(
      'containerOid' => 'Author:100',
      'insertOid' => 'Chapter:205',
      'referenceOid' => 'Chapter:204',
      'role' => 'Chapter'
    );
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->testChapterOrder([203, 205, 204], 100));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testInsertBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testChapterOrder([203, 204, 205], 100));

    // simulate a insertbefore call
    $data = array(
      'containerOid' => 'Author:100',
      'insertOid' => 'Chapter:203',
      'referenceOid' => 'ORDER_BOTTOM',
      'role' => 'Chapter'
    );
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->testChapterOrder([204, 205, 203], 100));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testNMInsertBeforeTop() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testNMBookOrder([304, 305, 306], 303));

    // simulate a insertbefore call
    $data = array(
      'containerOid' => 'Book:303',
      'insertOid' => 'Book:306',
      'referenceOid' => 'Book:304',
      'role' => 'ReferencedBook'
    );
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->testNMBookOrder([306, 304, 305], 303));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testNMInsertBeforeMiddle() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testNMBookOrder([304, 305, 306], 303));

    // simulate a insertbefore call
    $data = array(
      'containerOid' => 'Book:303',
      'insertOid' => 'Book:306',
      'referenceOid' => 'Book:305',
      'role' => 'ReferencedBook'
    );
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->testNMBookOrder([304, 306, 305], 303));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testNMInsertBeforeBottom() {
    TestUtil::startSession('admin', 'admin');

    // test before
    $this->assertTrue($this->testNMBookOrder([304, 305, 306], 303));

    // simulate a insertbefore call
    $data = array(
      'containerOid' => 'Book:303',
      'insertOid' => 'Book:304',
      'referenceOid' => 'ORDER_BOTTOM',
      'role' => 'ReferencedBook'
    );
    $this->runRequest('insertBefore', $data);

    // test
    $this->assertTrue($this->testNMBookOrder([305, 306, 304], 303));

    TestUtil::endSession();
  }

  private function testChapterOrder(array $order, $authorId=null) {
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

    private function testNMBookOrder(array $order, $bookId) {
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