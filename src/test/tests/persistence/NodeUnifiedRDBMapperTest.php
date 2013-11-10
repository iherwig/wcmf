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
namespace test\tests\persistence;

use app\src\model\Author;
use app\src\model\AuthorRDBMapper;
use app\src\model\PublisherRDBMapper;
use app\src\model\ImageRDBMapper;
use app\src\model\Chapter;
use app\src\model\ChapterRDBMapper;

use test\lib\BaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObjectProxy;

/**
 * NodeUnifiedRDBMapperTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUnifiedRDBMapperTest extends BaseTestCase {

  protected $dbParams;

  protected function setUp() {
    $this->dbParams = array('dbType' => 'mysql', 'dbHostName' => 'localhost',
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf_testapp', 'dbPrefix' => '');
  }

  public function testSelectSQL() {
    $mapper1 = new ChapterRDBMapper();
    $mapper1->setConnectionParams($this->dbParams);
    $criteria = new Criteria('Chapter', 'name', '=', 'Chapter 1');

    // condition 1
    $sql1 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL')->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql1));

    // condition 2
    $sql2 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` WHERE (`Chapter`.`name` = 'Chapter 1') ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql2));

    // alias
    $sql3 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL', array(array($criteria), "ChapterAlias"))->__toString();
    $expected = "SELECT `ChapterAlias`.`id`, `ChapterAlias`.`fk_chapter_id`, `ChapterAlias`.`fk_book_id`, `ChapterAlias`.`fk_author_id`, `ChapterAlias`.`name`, ".
      "`ChapterAlias`.`created`, `ChapterAlias`.`creator`, `ChapterAlias`.`modified`, `ChapterAlias`.`last_editor`, ".
      "`ChapterAlias`.`sortkey_author`, `ChapterAlias`.`sortkey_book`, `ChapterAlias`.`sortkey_parentchapter`, `ChapterAlias`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` AS `ChapterAlias` ".
      "LEFT JOIN `Author` ON `ChapterAlias`.`fk_author_id`=`Author`.`id` WHERE (`ChapterAlias`.`name` = 'Chapter 1') ".
      "ORDER BY `ChapterAlias`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql3));

    // order 1
    $sql4 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, array("name ASC")))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` ".
      "FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` WHERE (`Chapter`.`name` = 'Chapter 1') ".
      "ORDER BY `Chapter`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql4));

    // order 2
    $sql5 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, array("Chapter.name ASC")))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` ".
      "FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` WHERE (`Chapter`.`name` = 'Chapter 1') ".
      "ORDER BY `Chapter`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql5));

    // attribs 1
    $sql6 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name')))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`name` FROM `Chapter` WHERE (`Chapter`.`name` = 'Chapter 1') ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql6));

    // attribs 2
    $sql7 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name', 'author_name')))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`name`, `Author`.`name` AS `author_name` FROM `Chapter` ".
      "LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` WHERE (`Chapter`.`name` = 'Chapter 1') ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql7));

    // attribs 3
    $sql8 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, null, array()))->__toString();
    $expected = "SELECT `Chapter`.`id` FROM `Chapter` WHERE (`Chapter`.`name` = 'Chapter 1') ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql8));

    // dbprefix
    $this->dbParams['dbPrefix'] = 'WCMF_';
    $mapper2 = new ChapterRDBMapper();
    $mapper2->setConnectionParams($this->dbParams);

    // condition
    $sql9 = TestUtil::callProtectedMethod($mapper2, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `WCMF_Chapter`.`id`, `WCMF_Chapter`.`fk_chapter_id`, `WCMF_Chapter`.`fk_book_id`, `WCMF_Chapter`.`fk_author_id`, `WCMF_Chapter`.`name`, ".
      "`WCMF_Chapter`.`created`, `WCMF_Chapter`.`creator`, `WCMF_Chapter`.`modified`, `WCMF_Chapter`.`last_editor`, ".
      "`WCMF_Chapter`.`sortkey_author`, `WCMF_Chapter`.`sortkey_book`, `WCMF_Chapter`.`sortkey_parentchapter`, `WCMF_Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `WCMF_Chapter` LEFT JOIN `Author` ON `WCMF_Chapter`.`fk_author_id`=`Author`.`id` ".
      "WHERE (`WCMF_Chapter`.`name` = 'Chapter 1') ORDER BY `WCMF_Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql9));
  }

  public function testRelationSQL() {
    $mapper1 = new ChapterRDBMapper();
    $mapper1->setConnectionParams($this->dbParams);

    $chapter = new Chapter(new ObjectId('Chapter', array(1)));
    $chapter->setFkAuthorId(12);

    // parent (pk only)
    $relationDescription1 = $mapper1->getRelation('Author');
    $otherMapper1 = new AuthorRDBMapper();
    $otherMapper1->setConnectionParams($this->dbParams);
    $sql1 = TestUtil::callProtectedMethod($otherMapper1, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription1->getThisRole(), null, null, array()))->__toString();
    $expected1 = "SELECT `Author`.`id` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected1, str_replace("\n", "", $sql1));

    // parent (complete)
    $relationDescription2 = $mapper1->getRelation('Author');
    $otherMapper2 = new AuthorRDBMapper();
    $otherMapper2->setConnectionParams($this->dbParams);
    $sql2 = TestUtil::callProtectedMethod($otherMapper2, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription2->getThisRole()))->__toString();
    $expected2 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected2, str_replace("\n", "", $sql2));

    // parent (order)
    $relationDescription3 = $mapper1->getRelation('Author');
    $otherMapper3 = new AuthorRDBMapper();
    $otherMapper3->setConnectionParams($this->dbParams);
    $sql3 = TestUtil::callProtectedMethod($otherMapper3, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription3->getThisRole(), null, array('name')))->__toString();
    $expected3 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected3, str_replace("\n", "", $sql3));

    // parent (criteria)
    $criteria4 = new Criteria('Author', 'name', '=', 'Unknown');
    $relationDescription4 = $mapper1->getRelation('Author');
    $otherMapper4 = new AuthorRDBMapper();
    $otherMapper4->setConnectionParams($this->dbParams);
    $sql4 = TestUtil::callProtectedMethod($otherMapper4, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription4->getThisRole(), array($criteria4)))->__toString();
    $expected4 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id`= 12) AND (`Author`.`name` = 'Unknown') ".
      "ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected4, str_replace("\n", "", $sql4));

    // child (pk only)
    $relationDescription5 = $mapper1->getRelation('NormalImage');
    $otherMapper5 = new ImageRDBMapper();
    $otherMapper5->setConnectionParams($this->dbParams);
    $sql5 = TestUtil::callProtectedMethod($otherMapper5, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription5->getThisRole(), null, null, array()))->__toString();
    $expected5 = "SELECT `Image`.`id` FROM `Image` WHERE (`Image`.`fk_chapter_id`= 1) ORDER BY `Image`.`sortkey_normalchapter` ASC";
    $this->assertEquals($expected5, str_replace("\n", "", $sql5));

    // child (complete)
    $relationDescription6 = $mapper1->getRelation('NormalImage');
    $otherMapper6 = new ImageRDBMapper();
    $otherMapper6->setConnectionParams($this->dbParams);
    $sql6 = TestUtil::callProtectedMethod($otherMapper6, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription6->getThisRole()))->__toString();
    $expected6 = "SELECT `Image`.`id`, `Image`.`fk_chapter_id`, `Image`.`fk_titlechapter_id`, `Image`.`file` AS `filename`, ".
      "`Image`.`created`, `Image`.`creator`, `Image`.`modified`, `Image`.`last_editor`, ".
      "`Image`.`sortkey_titlechapter`, `Image`.`sortkey_normalchapter`, `Image`.`sortkey` ".
      "FROM `Image` WHERE (`Image`.`fk_chapter_id`= 1) ORDER BY `Image`.`sortkey_normalchapter` ASC";
    $this->assertEquals($expected6, str_replace("\n", "", $sql6));

    // many to many (pk only)
    $mapper2 = new PublisherRDBMapper();
    $mapper2->setConnectionParams($this->dbParams);
    $relationDescription7 = $mapper2->getRelation('Author');
    $otherMapper7 = new AuthorRDBMapper();
    $otherMapper7->setConnectionParams($this->dbParams);
    $sql7 = TestUtil::callProtectedMethod($otherMapper7, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription7->getThisRole(), null, null, array()))->__toString();
    $expected7 = "SELECT `Author`.`id`, `NMPublisherAuthor`.`sortkey_publisher` FROM `Author` INNER JOIN `NMPublisherAuthor` ON ".
      "`NMPublisherAuthor`.`fk_author_id`=`Author`.`id` WHERE (`NMPublisherAuthor`.`fk_publisher_id`= 1) ORDER BY `NMPublisherAuthor`.`sortkey_publisher` ASC";
    $this->assertEquals($expected7, str_replace("\n", "", $sql7));

    // many to many (complete)
    $relationDescription8 = $mapper2->getRelation('Author');
    $otherMapper8 = new AuthorRDBMapper();
    $otherMapper8->setConnectionParams($this->dbParams);
    $sql8 = TestUtil::callProtectedMethod($otherMapper8, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($chapter), $relationDescription8->getThisRole()))->__toString();
    $expected8 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor`, `NMPublisherAuthor`.`sortkey_publisher` FROM `Author` ".
      "INNER JOIN `NMPublisherAuthor` ON `NMPublisherAuthor`.`fk_author_id`=`Author`.`id` ".
      "WHERE (`NMPublisherAuthor`.`fk_publisher_id`= 1) ORDER BY `NMPublisherAuthor`.`sortkey_publisher` ASC";
    $this->assertEquals($expected8, str_replace("\n", "", $sql8));
  }

  public function testInsertSQL() {
    $mapper = new ChapterRDBMapper();
    $mapper->setConnectionParams($this->dbParams);

    $chapter1 = new Chapter(new ObjectId('Chapter', 1));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter1->setValue('created', '2010-02-21');
    $chapter1->setValue('creator', 'admin');

    $author = new Author(new ObjectId('Author', 2));
    $author->addNode($chapter1, 'Chapter');
    $chapter2 = new Chapter(new ObjectId('Chapter', 3));
    $chapter2->addNode($chapter1, 'SubChapter');

    TestUtil::callProtectedMethod($mapper, 'prepareForStorage', array($chapter1));
    $operations = TestUtil::callProtectedMethod($mapper, 'getInsertSQL', array($chapter1));
    $this->assertEquals(1, sizeof($operations));

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertEquals('wcmf\lib\persistence\InsertOperation:type=app.src.model.Chapter,values=(id=1,fk_chapter_id=3,fk_author_id=2,name=Chapter 1,created=2010-02-21,creator=admin),criteria=()', $str);
  }

  public function testUpdateSQL() {
    $mapper = new ChapterRDBMapper();
    $mapper->setConnectionParams($this->dbParams);

    $chapter1 = new Chapter(new ObjectId('Chapter', 1));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter1->setValue('created', '2010-02-21');
    $chapter1->setValue('creator', 'admin');

    $author = new Author(new ObjectId('Author', 2));
    $author->addNode($chapter1, 'Chapter');
    $chapter2 = new Chapter(new ObjectId('Chapter', 3));
    $chapter2->addNode($chapter1, 'SubChapter');

    TestUtil::callProtectedMethod($mapper, 'prepareForStorage', array($chapter1));
    $operations = TestUtil::callProtectedMethod($mapper, 'getUpdateSQL', array($chapter1));
    $this->assertEquals(1, sizeof($operations));

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertEquals('wcmf\lib\persistence\UpdateOperation:type=app.src.model.Chapter,values=(id=1,fk_chapter_id=3,fk_author_id=2,name=Chapter 1,created=2010-02-21,creator=admin),criteria=([AND] app.src.model.Chapter.id = 1)', $str);
  }

  public function testDeleteSQL() {
    $mapper = new ChapterRDBMapper();
    $mapper->setConnectionParams($this->dbParams);

    $operations = TestUtil::callProtectedMethod($mapper, 'getDeleteSQL', array(new ObjectId('Chapter', 1)));
    $this->assertEquals(1, sizeof($operations));

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertEquals('wcmf\lib\persistence\DeleteOperation:type=app.src.model.Chapter,values=(),criteria=([AND] app.src.model.Chapter.id = 1)', $str);
  }
}
?>