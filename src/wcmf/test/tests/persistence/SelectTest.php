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
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * SelectTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SelectTest extends DatabaseTestCase {

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
        array('id' => 201, 'fk_publisher_id' => 200, 'fk_author_id' => 203),
        array('id' => 202, 'fk_publisher_id' => 200, 'fk_author_id' => 204),
      ),
      'Author' => array(
        array('id' => 203),
        array('id' => 204),
      ),
      'Book' => array(
        array('id' => 205),
      ),
      'Chapter' => array(
        array('id' => 300, 'fk_chapter_id' => 303, 'fk_author_id' => 203, 'fk_book_id' => 205),
        array('id' => 302, 'fk_chapter_id' => 300, 'fk_author_id' => 203, 'fk_book_id' => null),
        array('id' => 303, 'fk_chapter_id' => null, 'fk_author_id' => 203, 'fk_book_id' => null),
      ),
      'Image' => array(
        array('id' => 305, 'fk_titlechapter_id' => 300, 'fk_chapter_id' => null),
        array('id' => 306, 'fk_titlechapter_id' => null, 'fk_chapter_id' => 300),
      ),
    ));
  }

  public function testSimple() {
    $selectId = __CLASS__.__METHOD__;
    $time = time();

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper('Author');
    $select = SelectStatement::get($mapper, $selectId);
    $select->setMeta('created', $time);
    $select->from('Author');
    $select->columns(array('id'));
    $select->where('id = :id');
    $select->setParameters(array(':id' => 203));
    $result = $select->query();
    $rows = $result->fetchAll();

    $this->assertEquals(1, sizeof($rows));
    $this->assertEquals(203, $rows[0][0]);
    $this->assertEquals(203, $rows[0]['id']);

    // test cache
    $select->save();
    $this->assertTrue($select->isCached());

    $select2 = SelectStatement::get($mapper, $selectId);
    $this->assertEquals($selectId, $select2->getId());
    $this->assertEquals($time, $select2->getMeta('created'));
  }

  public function testMapper() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper('Author');

    $criteria = new Criteria('Author', 'id', '=', 203);
    $select = TestUtil::callProtectedMethod($mapper, 'getSelectSQL', array(array($criteria)));
    $select->setParameters(array(':Author_id' => 203));
    $result = $select->query();
    $rows = $result->fetchAll();

    $this->assertEquals(1, sizeof($rows));
    $this->assertEquals(203, $rows[0][0]);
    $this->assertEquals(203, $rows[0]['id']);
  }
}
?>