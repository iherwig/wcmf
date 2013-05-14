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

use test\lib\BaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;

/**
 * ObjectQueryTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectQueryTest extends BaseTestCase {

  public function testSimple() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Author');
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor` FROM `Author` ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $cond = $query->getQueryCondition();
    $this->assertEquals('', $cond);

    TestUtil::runAnonymous(false);
  }

  public function testOneNode() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Author');
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", "admin"); // implicit LIKE
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' ".
      "AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $cond = $query->getQueryCondition();
    $this->assertEquals("(`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%')", $cond);

    TestUtil::runAnonymous(false);
  }

  public function testAttribs() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Author');
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", Criteria::asValue("IN", array("admin", "ingo"))); // explicit set
    //
    // we need to execute the query first in order to define the attributes
    $query->execute(BuildDepth::SINGLE, null, null, array('name'));
    $sql = $query->getLastQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' AND ".
      "`Author`.`creator` IN ('admin', 'ingo')) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testOrderby() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Author');
    //
    // we need to execute the query first in order to define the attributes
    $query->execute(BuildDepth::SINGLE, array('name ASC', 'created DESC'), null, array());
    $sql = $query->getLastQueryString();
    $expected = "SELECT `Author`.`id` FROM `Author` ORDER BY `Author`.`name` ASC, `Author`.`created` DESC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testOneNodeRegistered() {
    TestUtil::runAnonymous(true);

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $authorTpl = $persistenceFacade->create('Author', BuildDepth::SINGLE);
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", "admin"); // implicit LIKE

    $query = new ObjectQuery('Author');
    $query->registerObjectTemplate($authorTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' ".
      "AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testParentChild() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Author');
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $chapterTpl = $query->getObjectTemplate('Chapter');
    $chapterTpl->setValue("name", Criteria::asValue("LIKE", "Chapter 1%")); // explicit LIKE
    $authorTpl->addNode($chapterTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor` FROM `Author` INNER JOIN `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%') AND (`Chapter`.`name` LIKE 'Chapter 1%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testParentChildSameType() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Chapter');
    $page1Tpl = $query->getObjectTemplate('Chapter');
    $page1Tpl->setValue("creator", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $page2Tpl = $query->getObjectTemplate('Chapter');
    $page2Tpl->setValue("name", Criteria::asValue("LIKE", "Chapter 1%")); // explicit LIKE
    $page1Tpl->addNode($page2Tpl, 'SubChapter');
    $sql = $query->getQueryString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, ".
      "`Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` INNER JOIN `Chapter` AS `Chapter_1` ON ".
      "`Chapter_1`.`fk_chapter_id` = `Chapter`.`id` WHERE (`Chapter`.`creator` LIKE '%ingo%') AND (`Chapter_1`.`name` LIKE 'Chapter 1%') ".
      "ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testManyToMany() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Publisher');
    $publisherTpl = $query->getObjectTemplate('Publisher');
    $publisherTpl->setValue("name", Criteria::asValue("LIKE", "%Publisher 1%")); // explicit LIKE
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("=", "Author")); // explicit LIKE
    $publisherTpl->addNode($authorTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT `Publisher`.`id`, `Publisher`.`name`, `Publisher`.`created`, `Publisher`.`creator`, `Publisher`.`modified`, `Publisher`.`last_editor` ".
      "FROM `Publisher` INNER JOIN `NMPublisherAuthor` ON `NMPublisherAuthor`.`fk_publisher_id` = `Publisher`.`id` INNER JOIN `Author` ON `Author`.`id` = `NMPublisherAuthor`.`fk_author_id` ".
      "WHERE (`Publisher`.`name` LIKE '%Publisher 1%') AND (`Author`.`name` = 'Author') ORDER BY `Publisher`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testColumnNotEqualsAttribute() {
    TestUtil::runAnonymous(true);

    $oid = new ObjectId('UserRDB', array(2));
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    $tpl->setValue('sessionid', Criteria::asValue("=", "7pkt0i3ojm67s9qb66dih5nd60"));
    $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    $sql = $query->getQueryString();
    $expected = "SELECT `locktable`.`id`, `locktable`.`fk_user_id`, `locktable`.`objectid`, `locktable`.`sessionid`, ".
      "`locktable`.`since` FROM `locktable` WHERE (`locktable`.`objectid` = 'testapp.application.model.wcmf.UserRDB:2' AND ".
      "`locktable`.`sessionid` = '7pkt0i3ojm67s9qb66dih5nd60')";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testSortManyToManyRelation() {
    TestUtil::runAnonymous(true);

    $query = new ObjectQuery('Publisher');
    $publisherTpl = $query->getObjectTemplate('Publisher');
    $publisherTpl->setValue("name", Criteria::asValue("LIKE", "%Publisher 1%")); // explicit LIKE
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("=", "Author")); // explicit LIKE
    $publisherTpl->addNode($authorTpl);
    $sql = $query->getQueryString(array('sortkey_publisher DESC'));
    $expected = "SELECT `Publisher`.`id`, `Publisher`.`name`, `Publisher`.`created`, `Publisher`.`creator`, `Publisher`.`modified`, `Publisher`.`last_editor` ".
      "FROM `Publisher` INNER JOIN `NMPublisherAuthor` ON `NMPublisherAuthor`.`fk_publisher_id` = `Publisher`.`id` INNER JOIN `Author` ON `Author`.`id` = `NMPublisherAuthor`.`fk_author_id` ".
      "WHERE (`Publisher`.`name` LIKE '%Publisher 1%') AND (`Author`.`name` = 'Author') ORDER BY `NMPublisherAuthor`.`sortkey_publisher` DESC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testComplex() {
    TestUtil::runAnonymous(true);

    /*
    WHERE (Author.name LIKE '%ingo%' AND Author.creator LIKE '%admin%') OR (Author.name LIKE '%herwig%') AND
      (Chapter.created >= '2004-01-01') AND (Chapter.created < '2005-01-01') AND ((Chapter.name LIKE 'Chapter 1%') OR (Chapter.creator = 'admin'))
     */

    $query = new ObjectQuery('Author');

    // (Author.name LIKE '%ingo%' AND Author.creator LIKE '%admin%')
    $authorTpl1 = $query->getObjectTemplate('Author');
    $authorTpl1->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl1->setValue("creator", "admin"); // implicit LIKE

    // OR (Author.name LIKE '%herwig%')
    $authorTpl2 = $query->getObjectTemplate('Author', null, Criteria::OPERATOR_OR);
    $authorTpl2->setValue("name", "herwig");

    // (Chapter.created >= '2004-01-01') AND (Chapter.created < '2005-01-01')
    $pageTpl1 = $query->getObjectTemplate('Chapter');
    $pageTpl1->setValue("created", Criteria::asValue(">=", "2004-01-01"));
    $pageTpl2 = $query->getObjectTemplate('Chapter');
    $pageTpl2->setValue("created", Criteria::asValue("<", "2005-01-01"));

    // AND ((Chapter.name LIKE 'Chapter 1%') OR (Chapter.creator = 'admin'))
    // could have be built using one template, but this demonstrates the usage
    // of the ObjectQuery::makeGroup() method
    $pageTpl3 = $query->getObjectTemplate('Chapter');
    $pageTpl3->setValue("name", Criteria::asValue("LIKE", "Chapter 1%"));
    $pageTpl4 = $query->getObjectTemplate('Chapter', null, Criteria::OPERATOR_OR);
    $pageTpl4->setValue("creator", Criteria::asValue("=", "admin"));
    $query->makeGroup(array(&$pageTpl3, &$pageTpl4), Criteria::OPERATOR_AND);

    $authorTpl1->addNode($pageTpl1, 'Chapter');
    $authorTpl1->addNode($pageTpl2, 'Chapter');
    $authorTpl1->addNode($pageTpl3, 'Chapter');
    $authorTpl1->addNode($pageTpl4, 'Chapter');
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor` FROM `Author` INNER JOIN `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%') AND (`Chapter`.`created` >= '2004-01-01') ".
      "AND (`Chapter`.`created` < '2005-01-01') OR (`Author`.`name` LIKE '%herwig%') AND ".
      "((`Chapter`.`name` LIKE 'Chapter 1%') OR (`Chapter`.`creator` = 'admin')) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }
}
?>