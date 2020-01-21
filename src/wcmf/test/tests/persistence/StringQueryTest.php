<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * StringQueryTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StringQueryTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ],
      'Author' => [
        ['id' => 100, 'name' => 'ingo', 'creator' => 'admin'],
      ],
      'Chapter' => [
        ['id' => 300, 'name' => 'Chapter A'],
        ['id' => 301, 'name' => 'Chapter B'],
        ['id' => 302, 'name' => 'Chapter C'],
      ],
    ]);
  }

  public function testSimple() {
    $queryStr = $this->fixQueryQuotes("`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%'", 'Author');
    $query = new StringQuery('Author', __CLASS__.__METHOD__."1");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, ".
      "`Author`.`modified` AS `modified`, `Author`.`last_editor` AS `last_editor` ".
      "FROM `Author` WHERE `Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%' ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
    $this->executeSql('Author', $sql);
  }

  public function testNoCondition() {
    $query = new StringQuery('Author', __CLASS__.__METHOD__."2");
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, ".
      "`Author`.`modified` AS `modified`, `Author`.`last_editor` AS `last_editor` ".
      "FROM `Author` ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
    $this->executeSql('Author', $sql);
  }

  public function testParentChild() {
    $queryStr = $this->fixQueryQuotes("`Author`.`name` LIKE '%ingo%' AND `Chapter`.`name` LIKE 'Chapter 1%'", 'Author');
    $query = new StringQuery('Author', __CLASS__.__METHOD__."3");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, ".
      "`Author`.`modified` AS `modified`, `Author`.`last_editor` AS `last_editor` ".
      "FROM `Author` INNER JOIN `Chapter` AS `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "WHERE `Author`.`name` LIKE '%ingo%' AND `Chapter`.`name` LIKE 'Chapter 1%' ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
    $this->executeSql('Author', $sql);
  }

  public function testParentChildSameType() {
    $queryStr = $this->fixQueryQuotes("`Chapter`.`creator` LIKE '%ingo%' AND `SubChapter`.`name` LIKE 'Chapter 1%'", 'Chapter');
    $query = new StringQuery('Chapter', __CLASS__.__METHOD__."4");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Chapter`.`id` AS `id`, `Chapter`.`fk_chapter_id` AS `fk_chapter_id`, `Chapter`.`fk_book_id` AS `fk_book_id`, `Chapter`.`fk_author_id` AS `fk_author_id`, ".
      "`Chapter`.`name` AS `name`, `Chapter`.`content` AS `content`, `Chapter`.`created` AS `created`, ".
      "`Chapter`.`creator` AS `creator`, `Chapter`.`modified` AS `modified`, `Chapter`.`last_editor` AS `last_editor`, `Chapter`.`sortkey_author` AS `sortkey_author`, `Chapter`.`sortkey_book` AS `sortkey_book`, ".
      "`Chapter`.`sortkey_parentchapter` AS `sortkey_parentchapter`, `Chapter`.`sortkey` AS `sortkey`, ".
      "`AuthorRef`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` AS `AuthorRef` ON `Chapter`.`fk_author_id`=`AuthorRef`.`id` INNER JOIN `Chapter` AS `SubChapter` ON ".
      "`SubChapter`.`fk_chapter_id` = `Chapter`.`id` WHERE `Chapter`.`creator` LIKE '%ingo%' AND `SubChapter`.`name` ".
      "LIKE 'Chapter 1%' ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql));
    $this->executeSql('Chapter', $sql);
  }

  public function testChildParent() {
    $queryStr = $this->fixQueryQuotes("`NormalChapter`.`id` = 10", 'Chapter');
    $query = new StringQuery('Image', __CLASS__.__METHOD__."5");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Image`.`id` AS `id`, `Image`.`fk_chapter_id` AS `fk_chapter_id`, `Image`.`fk_titlechapter_id` AS `fk_titlechapter_id`, ".
      "`Image`.`file` AS `filename`, `Image`.`created` AS `created`, `Image`.`creator` AS `creator`, `Image`.`modified` AS `modified`, `Image`.`last_editor` AS `last_editor`, ".
      "`Image`.`sortkey_titlechapter` AS `sortkey_titlechapter`, `Image`.`sortkey_normalchapter` AS `sortkey_normalchapter`, `Image`.`sortkey` AS `sortkey` ".
      "FROM `Image` INNER JOIN `Chapter` AS `NormalChapter` ON ".
      "`Image`.`fk_chapter_id` = `NormalChapter`.`id` WHERE `NormalChapter`.`id` = 10 ORDER BY `Image`.`sortkey` DESC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Image'), str_replace("\n", "", $sql));
    $this->executeSql('Image', $sql);
  }

  public function testChildParentSameType() {
    $queryStr = $this->fixQueryQuotes("`ParentChapter`.`id` = 10", 'Chapter');
    $query = new StringQuery('Chapter', __CLASS__.__METHOD__."6");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Chapter`.`id` AS `id`, `Chapter`.`fk_chapter_id` AS `fk_chapter_id`, `Chapter`.`fk_book_id` AS `fk_book_id`, `Chapter`.`fk_author_id` AS `fk_author_id`, ".
      "`Chapter`.`name` AS `name`, `Chapter`.`content` AS `content`, `Chapter`.`created` AS `created`, ".
      "`Chapter`.`creator` AS `creator`, `Chapter`.`modified` AS `modified`, `Chapter`.`last_editor` AS `last_editor`, `Chapter`.`sortkey_author` AS `sortkey_author`, ".
      "`Chapter`.`sortkey_book` AS `sortkey_book`, `Chapter`.`sortkey_parentchapter` AS `sortkey_parentchapter`, `Chapter`.`sortkey` AS `sortkey`, ".
      "`AuthorRef`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` AS `AuthorRef` ON `Chapter`.`fk_author_id`=`AuthorRef`.`id` INNER JOIN `Chapter` AS `ParentChapter` ON ".
      "`Chapter`.`fk_chapter_id` = `ParentChapter`.`id` WHERE `ParentChapter`.`id` = 10 ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql));
    $this->executeSql('Chapter', $sql);
  }

  public function testManyToMany() {
    $queryStr = $this->fixQueryQuotes("`Publisher`.`name` LIKE '%Publisher 1%' AND `Author`.`name` = 'Author 2'", 'Publisher');
    $query = new StringQuery('Publisher', __CLASS__.__METHOD__."7");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Publisher`.`id` AS `id`, `Publisher`.`name` AS `name`, ".
      "`Publisher`.`created` AS `created`, `Publisher`.`creator` AS `creator`, `Publisher`.`modified` AS `modified`, `Publisher`.`last_editor` AS `last_editor` ".
      "FROM `Publisher` INNER JOIN `NMPublisherAuthor` AS `NMPublisherAuthorAuthor` ON `NMPublisherAuthorAuthor`.`fk_publisher_id` = `Publisher`.`id` ".
      "INNER JOIN `Author` AS `Author` ON `Author`.`id` = `NMPublisherAuthorAuthor`.`fk_author_id` ".
      "WHERE `Publisher`.`name` LIKE '%Publisher 1%' AND `Author`.`name` = 'Author 2' ORDER BY `Publisher`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Publisher'), str_replace("\n", "", $sql));
    $this->executeSql('Publisher', $sql);
  }

  /**
   * @expectedException wcmf\lib\core\IllegalArgumentException
   */
  public function testAmbiguousRelation() {
    $queryStr = $this->fixQueryQuotes("`Author`.`name` LIKE '%ingo%' AND `Image`.`file` = 'image.jpg'", 'Author');
    $query = new StringQuery('Author', __CLASS__.__METHOD__."8");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "INNER JOIN `Image` AS `NormalImage` ON `NormalImage`.`fk_chapter_id` = `Chapter`.`id` INNER JOIN `Image` AS `TitleImage` ON ".
      "`TitleImage`.`fk_titlechapter_id` = `Chapter`.`id` WHERE (`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
    $this->executeSql('Author', $sql);
  }

  public function testDifferentRoles() {
    $queryStr = $this->fixQueryQuotes("`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`filename` = 'image.jpg' ".
      "AND `TitleImage`.`filename` = 'title_image.jpg'", 'Author');
    $query = new StringQuery('Author', __CLASS__.__METHOD__."9");
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, ".
      "`Author`.`last_editor` AS `last_editor` FROM `Author` INNER JOIN `Chapter` AS `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "INNER JOIN `Image` AS `NormalImage` ON `NormalImage`.`fk_chapter_id` = `Chapter`.`id` INNER JOIN `Image` AS `TitleImage` ON ".
      "`TitleImage`.`fk_titlechapter_id` = `Chapter`.`id` WHERE `Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg' ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
    $this->executeSql('Author', $sql);
  }

  public function testWithDB() {
    TestUtil::startSession('admin', 'admin');
    $query = new StringQuery('Chapter', __CLASS__.__METHOD__."10");
    $query->setConditionString($this->fixQueryQuotes("`Chapter`.`name` LIKE 'Chapter A'", 'Chapter'));
    $chapterList = $query->execute(BuildDepth::SINGLE);

    $this->assertEquals(1, sizeof($chapterList));
    $this->assertEquals('Chapter A', $chapterList[0]->getValue('name'));
    TestUtil::endSession();
  }

  public function testOrderByMappedColumn() {
    $query = new StringQuery('Image', __CLASS__.__METHOD__."11");
    $sql = $query->getQueryString(BuildDepth::SINGLE, ['filename ASC', 'created DESC']);
    $expected = "SELECT DISTINCT `Image`.`id` AS `id`, `Image`.`fk_chapter_id` AS `fk_chapter_id`, `Image`.`fk_titlechapter_id` AS `fk_titlechapter_id`, ".
      "`Image`.`file` AS `filename`, `Image`.`created` AS `created`, `Image`.`creator` AS `creator`, `Image`.`modified` AS `modified`, `Image`.`last_editor` AS `last_editor`, ".
      "`Image`.`sortkey_titlechapter` AS `sortkey_titlechapter`, `Image`.`sortkey_normalchapter` AS `sortkey_normalchapter`, `Image`.`sortkey` AS `sortkey`, `Image`.`file` AS `file` ".
      "FROM `Image` ORDER BY `Image`.`file` ASC, `Image`.`created` DESC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Image'), str_replace("\n", "", $sql));
    $this->executeSql('Image', $sql);
  }

  public function testRQL() {
    $queryStr = $this->fixQueryQuotes("(Author.name=in=A1,A2|Author.name=in=A4,A5)&Author.created=gte=2000", 'Author');
    $query = new StringQuery('Author', __CLASS__.__METHOD__."12");
    $query->setRQLConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, ".
      "`Author`.`last_editor` AS `last_editor` FROM `Author` WHERE ".
      "(Author.name in ('A1','A2') OR Author.name in ('A4','A5')) AND Author.created >= '2000' ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
    $this->executeSql('Author', $sql);
  }
}
?>