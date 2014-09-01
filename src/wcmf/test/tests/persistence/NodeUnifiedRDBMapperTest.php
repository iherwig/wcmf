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

use app\src\model\Author;
use app\src\model\AuthorRDBMapper;
use app\src\model\PublisherRDBMapper;
use app\src\model\ImageRDBMapper;
use app\src\model\Chapter;
use app\src\model\ChapterRDBMapper;

use wcmf\test\lib\BaseTestCase;
use wcmf\test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
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
    parent::setUp();
    $config = ObjectFactory::getConfigurationInstance();
    $this->dbParams = $config->getSection('database');
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
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql1));

    // condition 2
    $sql2 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` WHERE (`Chapter`.`name` = :Chapter_name) ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql2));

    // alias
    $sql3 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL', array(array($criteria), "ChapterAlias"))->__toString();
    $expected = "SELECT `ChapterAlias`.`id`, `ChapterAlias`.`fk_chapter_id`, `ChapterAlias`.`fk_book_id`, `ChapterAlias`.`fk_author_id`, `ChapterAlias`.`name`, ".
      "`ChapterAlias`.`created`, `ChapterAlias`.`creator`, `ChapterAlias`.`modified`, `ChapterAlias`.`last_editor`, ".
      "`ChapterAlias`.`sortkey_author`, `ChapterAlias`.`sortkey_book`, `ChapterAlias`.`sortkey_parentchapter`, `ChapterAlias`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` AS `ChapterAlias` ".
      "LEFT JOIN `Author` ON `ChapterAlias`.`fk_author_id`=`Author`.`id` WHERE (`ChapterAlias`.`name` = :ChapterAlias_name) ".
      "ORDER BY `ChapterAlias`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql3));

    // order 1
    $sql4 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, array("name ASC")))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` ".
      "FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` WHERE (`Chapter`.`name` = :Chapter_name) ".
      "ORDER BY `Chapter`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql4));

    // order 2
    $sql5 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, array("Chapter.name ASC")))->__toString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` ".
      "FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` WHERE (`Chapter`.`name` = :Chapter_name) ".
      "ORDER BY `Chapter`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql5));

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
      "WHERE (`WCMF_Chapter`.`name` = :WCMF_Chapter_name) ORDER BY `WCMF_Chapter`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql9));
  }

  public function testRelationSQL() {
    $mapper1 = new ChapterRDBMapper();
    $mapper1->setConnectionParams($this->dbParams);

    $chapter = new Chapter(new ObjectId('Chapter', array(1)));
    $chapter->setValue('fk_author_id', 12);

    // parent (complete)
    $relationDescription2 = $mapper1->getRelation('Author');
    $otherMapper2 = new AuthorRDBMapper();
    $otherMapper2->setConnectionParams($this->dbParams);
    list($selectStmt2, $objValueName2, $relValueName2) = TestUtil::callProtectedMethod($otherMapper2, 'getRelationSelectSQL',
            array(array(PersistentObjectProxy::fromObject($chapter)), $relationDescription2->getThisRole()));
    $this->assertEquals('fk_author_id', $objValueName2);
    $this->assertEquals('id', $relValueName2);
    $sql2 = $selectStmt2->__toString();
    $expected2 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id` IN(:Author_id0)) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected2, 'Author'), str_replace("\n", "", $sql2));

    // parent (order)
    $relationDescription3 = $mapper1->getRelation('Author');
    $otherMapper3 = new AuthorRDBMapper();
    $otherMapper3->setConnectionParams($this->dbParams);
    list($selectStmt3, $objValueName3, $relValueName3) = TestUtil::callProtectedMethod($otherMapper3, 'getRelationSelectSQL',
            array(array(PersistentObjectProxy::fromObject($chapter)), $relationDescription3->getThisRole(), null, array('name')));
    $this->assertEquals('fk_author_id', $objValueName3);
    $this->assertEquals('id', $relValueName3);
    $sql3 = $selectStmt3->__toString();
    $expected3 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id` IN(:Author_id0)) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected3, 'Author'), str_replace("\n", "", $sql3));

    // parent (criteria)
    $criteria4 = new Criteria('Author', 'name', '=', 'Unknown');
    $relationDescription4 = $mapper1->getRelation('Author');
    $otherMapper4 = new AuthorRDBMapper();
    $otherMapper4->setConnectionParams($this->dbParams);
    list($selectStmt4, $objValueName4, $relValueName4) = TestUtil::callProtectedMethod($otherMapper4, 'getRelationSelectSQL',
            array(array(PersistentObjectProxy::fromObject($chapter)), $relationDescription4->getThisRole(), array($criteria4)));
    $this->assertEquals('fk_author_id', $objValueName4);
    $this->assertEquals('id', $relValueName4);
    $sql4 = $selectStmt4->__toString();
    $expected4 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id` IN(:Author_id0)) AND (`Author`.`name` = :Author_name) ".
      "ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected4, 'Author'), str_replace("\n", "", $sql4));

    // child (complete)
    $relationDescription6 = $mapper1->getRelation('NormalImage');
    $otherMapper6 = new ImageRDBMapper();
    $otherMapper6->setConnectionParams($this->dbParams);
    list($selectStmt6, $objValueName6, $relValueName6) = TestUtil::callProtectedMethod($otherMapper6, 'getRelationSelectSQL',
            array(array(PersistentObjectProxy::fromObject($chapter)), $relationDescription6->getThisRole()));
    $this->assertEquals('id', $objValueName6);
    $this->assertEquals('fk_chapter_id', $relValueName6);
    $sql6 = $selectStmt6->__toString();
    $expected6 = "SELECT `Image`.`id`, `Image`.`fk_chapter_id`, `Image`.`fk_titlechapter_id`, `Image`.`file` AS `filename`, ".
      "`Image`.`created`, `Image`.`creator`, `Image`.`modified`, `Image`.`last_editor`, ".
      "`Image`.`sortkey_titlechapter`, `Image`.`sortkey_normalchapter`, `Image`.`sortkey` ".
      "FROM `Image` WHERE (`Image`.`fk_chapter_id` IN(:Image_fk_chapter_id0)) ORDER BY `Image`.`sortkey_normalchapter` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected6, 'Author'), str_replace("\n", "", $sql6));

    $mapper2 = new PublisherRDBMapper();

    // many to many (complete)
    $relationDescription8 = $mapper2->getRelation('Author');
    $otherMapper8 = new AuthorRDBMapper();
    $otherMapper8->setConnectionParams($this->dbParams);
    list($selectStmt8, $objValueName8, $relValueName8) = TestUtil::callProtectedMethod($otherMapper8, 'getRelationSelectSQL',
            array(array(PersistentObjectProxy::fromObject($chapter)), $relationDescription8->getThisRole()));
    $this->assertEquals('id', $objValueName8);
    $this->assertEquals('_mapper_internal_id', $relValueName8);
    $sql8 = $selectStmt8->__toString();
    $expected8 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor`, `NMPublisherAuthor`.`sortkey_publisher`, `NMPublisherAuthor`.`fk_publisher_id` AS `_mapper_internal_id` FROM `Author` ".
      "INNER JOIN `NMPublisherAuthor` ON `NMPublisherAuthor`.`fk_author_id`=`Author`.`id` ".
      "WHERE (`NMPublisherAuthor`.`fk_publisher_id` IN(:NMPublisherAuthor_fk_publisher_id0)) ORDER BY `NMPublisherAuthor`.`sortkey_publisher` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected8, 'Author'), str_replace("\n", "", $sql8));
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
    $this->assertEquals('wcmf\lib\persistence\InsertOperation:type=app.src.model.Chapter,values='
            . '(id=1,fk_chapter_id=3,fk_book_id=NULL,fk_author_id=2,name=Chapter 1,created=2010-02-21,creator=admin,'
            . 'modified=NULL,last_editor=NULL,sortkey_author=NULL,sortkey_book=NULL,sortkey_parentchapter=NULL,sortkey=NULL),'
            . 'criteria=()', $str);
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
    $this->assertEquals('wcmf\lib\persistence\UpdateOperation:type=app.src.model.Chapter,values='
            . '(id=1,fk_chapter_id=3,fk_book_id=NULL,fk_author_id=2,name=Chapter 1,created=2010-02-21,creator=admin,'
            . 'modified=NULL,last_editor=NULL,sortkey_author=NULL,sortkey_book=NULL,sortkey_parentchapter=NULL,sortkey=NULL),'
            . 'criteria=([AND] app.src.model.Chapter.id = 1)', $str);
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