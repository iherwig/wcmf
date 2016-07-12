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
namespace wcmf\test\tests\persistence;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * ObjectQueryTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectQueryTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'config' => ''),
      ),
      'Chapter' => array(
        array('id' => 300, 'name' => 'Chapter A'),
        array('id' => 301, 'name' => 'Chapter B'),
        array('id' => 302, 'name' => 'Chapter C'),
      ),
    ));
  }

  public function testSimple() {
    $query = new ObjectQuery('Author', __CLASS__.__METHOD__."1");
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, ".
      "`Author`.`last_editor` AS `last_editor` FROM `Author` AS `Author` ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));

    $cond = $query->getQueryCondition();
    $this->assertEquals('', $cond);
  }

  public function testOid() {
    $query = new ObjectQuery('Author', __CLASS__.__METHOD__."1");
    $sql = $query->getQueryString(false);
    $expected = "SELECT DISTINCT `Author`.`id` AS `id` ".
      "FROM `Author` AS `Author` ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));

    $cond = $query->getQueryCondition();
    $this->assertEquals('', $cond);
  }

  public function testOneNode() {
    $query = new ObjectQuery('Author', __CLASS__.__METHOD__."2");
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", "admin"); // implicit LIKE
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, ".
      "`Author`.`last_editor` AS `last_editor` FROM `Author` AS `Author` WHERE (`Author`.`name` LIKE '%ingo%' ".
      "AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));

    $cond = $query->getQueryCondition();
    $this->assertEquals($this->fixQueryQuotes("(`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%')", 'Author'), $cond);
  }

  public function testOrderby() {
    $query = new ObjectQuery('Author', __CLASS__.__METHOD__."3");
    $sql = $query->getQueryString(BuildDepth::SINGLE, array('name ASC', 'created DESC'));
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, `Author`.`last_editor` AS `last_editor` ".
      "FROM `Author` AS `Author` ORDER BY `Author`.`name` ASC, `Author`.`created` DESC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
  }

  public function testOneNodeRegistered() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $authorTpl = $persistenceFacade->create('Author', BuildDepth::SINGLE);
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", "admin"); // implicit LIKE

    $query = new ObjectQuery('Author', __CLASS__.__METHOD__."4");
    $query->registerObjectTemplate($authorTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, ".
      "`Author`.`last_editor` AS `last_editor` FROM `Author` AS `Author` WHERE (`Author`.`name` LIKE '%ingo%' ".
      "AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
  }

  public function testParentChild() {
    $query = new ObjectQuery('Author', __CLASS__.__METHOD__."5");
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $chapterTpl = $query->getObjectTemplate('Chapter');
    $chapterTpl->setValue("name", Criteria::asValue("LIKE", "Chapter 1%")); // explicit LIKE
    $authorTpl->addNode($chapterTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, ".
      "`Author`.`last_editor` AS `last_editor` FROM `Author` AS `Author` INNER JOIN `Chapter` AS `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%') AND (`Chapter`.`name` LIKE 'Chapter 1%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
  }

  public function testParentChildSameType() {
    $query = new ObjectQuery('Chapter', __CLASS__.__METHOD__."6");
    $page1Tpl = $query->getObjectTemplate('Chapter');
    $page1Tpl->setValue("creator", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $page2Tpl = $query->getObjectTemplate('Chapter');
    $page2Tpl->setValue("name", Criteria::asValue("LIKE", "Chapter 1%")); // explicit LIKE
    $page1Tpl->addNode($page2Tpl, 'SubChapter');
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Chapter`.`id` AS `id`, `Chapter`.`fk_chapter_id` AS `fk_chapter_id`, `Chapter`.`fk_book_id` AS `fk_book_id`, `Chapter`.`fk_author_id` AS `fk_author_id`, ".
      "`Chapter`.`name` AS `name`, `Chapter`.`content` AS `content`, `Chapter`.`created` AS `created`, `Chapter`.`creator` AS `creator`, `Chapter`.`modified` AS `modified`, `Chapter`.`last_editor` AS `last_editor`, ".
      "`Chapter`.`sortkey_author` AS `sortkey_author`, `Chapter`.`sortkey_book` AS `sortkey_book`, `Chapter`.`sortkey_parentchapter` AS `sortkey_parentchapter`, `Chapter`.`sortkey` AS `sortkey`, ".
      "`AuthorRef`.`name` AS `author_name` FROM `Chapter` AS `Chapter` LEFT JOIN `Author` AS `AuthorRef` ON `Chapter`.`fk_author_id`=`AuthorRef`.`id` INNER JOIN `Chapter` AS `Chapter_1` ON ".
      "`Chapter_1`.`fk_chapter_id` = `Chapter`.`id` WHERE (`Chapter`.`creator` LIKE '%ingo%') AND (`Chapter_1`.`name` LIKE 'Chapter 1%') ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Chapter'), str_replace("\n", "", $sql));
  }

  public function testManyToMany() {
    $query = new ObjectQuery('Publisher', __CLASS__.__METHOD__."7");
    $publisherTpl = $query->getObjectTemplate('Publisher');
    $publisherTpl->setValue("name", Criteria::asValue("LIKE", "%Publisher 1%")); // explicit LIKE
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("=", "Author")); // explicit LIKE
    $publisherTpl->addNode($authorTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Publisher`.`id` AS `id`, `Publisher`.`name` AS `name`, `Publisher`.`created` AS `created`, `Publisher`.`creator` AS `creator`, ".
      "`Publisher`.`modified` AS `modified`, `Publisher`.`last_editor` AS `last_editor` ".
      "FROM `Publisher` AS `Publisher` INNER JOIN `NMPublisherAuthor` ON `NMPublisherAuthor`.`fk_publisher_id` = `Publisher`.`id` INNER JOIN `Author` AS `Author` ON `Author`.`id` = `NMPublisherAuthor`.`fk_author_id` ".
      "WHERE (`Publisher`.`name` LIKE '%Publisher 1%') AND (`Author`.`name` = 'Author') ORDER BY `Publisher`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Publisher'), str_replace("\n", "", $sql));
  }

  public function testSortManyToManyRelation() {
    $query = new ObjectQuery('Publisher', __CLASS__.__METHOD__."9");
    $publisherTpl = $query->getObjectTemplate('Publisher');
    $publisherTpl->setValue("name", Criteria::asValue("LIKE", "%Publisher 1%")); // explicit LIKE
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("=", "Author")); // explicit LIKE
    $publisherTpl->addNode($authorTpl);
    $sql = $query->getQueryString(BuildDepth::SINGLE, array('sortkey_publisher DESC'));
    $expected = "SELECT DISTINCT `Publisher`.`id` AS `id`, `Publisher`.`name` AS `name`, `Publisher`.`created` AS `created`, `Publisher`.`creator` AS `creator`, `Publisher`.`modified` AS `modified`, `Publisher`.`last_editor` AS `last_editor` ".
      "FROM `Publisher` AS `Publisher` INNER JOIN `NMPublisherAuthor` ON `NMPublisherAuthor`.`fk_publisher_id` = `Publisher`.`id` INNER JOIN `Author` AS `Author` ON `Author`.`id` = `NMPublisherAuthor`.`fk_author_id` ".
      "WHERE (`Publisher`.`name` LIKE '%Publisher 1%') AND (`Author`.`name` = 'Author') ORDER BY `NMPublisherAuthor`.`sortkey_publisher` DESC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Publisher'), str_replace("\n", "", $sql));
  }

  public function testComplex() {
    /*
    WHERE (Author.name LIKE '%ingo%' AND Author.creator LIKE '%admin%') OR (Author.name LIKE '%herwig%') AND
      (Chapter.created >= '2004-01-01') AND (Chapter.created < '2005-01-01') AND ((Chapter.name LIKE 'Chapter 1%') OR (Chapter.creator = 'admin'))
     */

    $query = new ObjectQuery('Author', __CLASS__.__METHOD__."10");

    // (Author.name LIKE '%ingo%' AND Author.creator LIKE '%admin%')
    $authorTpl1 = $query->getObjectTemplate('Author');
    $authorTpl1->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl1->setValue("creator", "admin"); // implicit LIKE

    // OR (Author.name LIKE '%herwig%')
    $authorTpl2 = $query->getObjectTemplate('Author', null, Criteria::OPERATOR_OR);
    $authorTpl2->setValue("name", "herwig");

    // AND (Chapter.created >= '2004-01-01') AND (Chapter.created < '2005-01-01')
    $chapterTpl1 = $query->getObjectTemplate('Chapter');
    $chapterTpl1->setValue("created", Criteria::asValue(">=", "2004-01-01"));
    $chapterTpl2 = $query->getObjectTemplate('Chapter');
    $chapterTpl2->setValue("created", Criteria::asValue("<", "2005-01-01"));

    // AND ((Chapter.name LIKE 'Chapter 1%') OR (Chapter.creator = 'admin'))
    // could have be built using one template, but this demonstrates the usage
    // of the ObjectQuery::makeGroup() method
    $chapterTpl3 = $query->getObjectTemplate('Chapter');
    $chapterTpl3->setValue("name", Criteria::asValue("LIKE", "Chapter 1%"));
    $chapterTpl4 = $query->getObjectTemplate('Chapter', null, Criteria::OPERATOR_OR);
    $chapterTpl4->setValue("creator", Criteria::asValue("=", "admin"));
    $query->makeGroup(array(&$chapterTpl3, &$chapterTpl4), Criteria::OPERATOR_AND);

    $authorTpl1->addNode($chapterTpl1, 'Chapter');
    $authorTpl1->addNode($chapterTpl2, 'Chapter');
    $authorTpl1->addNode($chapterTpl3, 'Chapter');
    $authorTpl1->addNode($chapterTpl4, 'Chapter');
    $sql = $query->getQueryString();
    $expected = "SELECT DISTINCT `Author`.`id` AS `id`, `Author`.`name` AS `name`, `Author`.`created` AS `created`, `Author`.`creator` AS `creator`, `Author`.`modified` AS `modified`, ".
      "`Author`.`last_editor` AS `last_editor` FROM `Author` AS `Author` INNER JOIN `Chapter` AS `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%') AND (`Chapter`.`created` >= '2004-01-01') ".
      "AND (`Chapter`.`created` < '2005-01-01') OR (`Author`.`name` LIKE '%herwig%') AND ".
      "((`Chapter`.`name` LIKE 'Chapter 1%') OR (`Chapter`.`creator` = 'admin')) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected, 'Author'), str_replace("\n", "", $sql));
  }

  public function testWithDB() {
    TestUtil::startSession('admin', 'admin');
    $query = new ObjectQuery('Chapter', __CLASS__.__METHOD__."1");
    $chapter = $query->getObjectTemplate('Chapter');
    $chapter->setValue('name', Criteria::asValue("LIKE", "Chapter A"));
    $chapterList = $query->execute(BuildDepth::SINGLE);

    $this->assertEquals(1, sizeof($chapterList));
    $this->assertEquals('Chapter A', $chapterList[0]->getValue('name'));
    TestUtil::endSession();
  }
}
?>