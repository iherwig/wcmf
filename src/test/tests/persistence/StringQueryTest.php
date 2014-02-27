<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace test\tests\persistence;

use test\lib\BaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\model\StringQuery;

/**
 * StringQueryTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StringQueryTest extends BaseTestCase {

  public function testSimple() {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, `Author`.`last_editor` ".
      "FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testNoCondition() {
    TestUtil::runAnonymous(true);

    $query = new StringQuery('Author');
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, `Author`.`last_editor` ".
      "FROM `Author` ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testParentChild() {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Chapter`.`name` LIKE 'Chapter 1%'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor` FROM `Author` INNER JOIN `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%' AND `Chapter`.`name` LIKE 'Chapter 1%') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testParentChildSameType() {
    TestUtil::runAnonymous(true);

    $queryStr = "`Chapter`.`creator` LIKE '%ingo%' AND `SubChapter`.`name` LIKE 'Chapter 1%'";
    $query = new StringQuery('Chapter');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` INNER JOIN `Chapter` AS `SubChapter` ON ".
      "`SubChapter`.`fk_chapter_id` = `Chapter`.`id` WHERE (`Chapter`.`creator` LIKE '%ingo%' AND `SubChapter`.`name` ".
      "LIKE 'Chapter 1%') ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testChildParent() {
    TestUtil::runAnonymous(true);

    $queryStr = "`NormalChapter`.`id` = 10";
    $query = new StringQuery('Image');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Image`.`id`, `Image`.`fk_chapter_id`, `Image`.`fk_titlechapter_id`, `Image`.`file` AS `filename`, `Image`.`created`, ".
      "`Image`.`creator`, `Image`.`modified`, `Image`.`last_editor`, `Image`.`sortkey_titlechapter`, `Image`.`sortkey_normalchapter`, `Image`.`sortkey` ".
      "FROM `Image` INNER JOIN `Chapter` AS `NormalChapter` ON ".
      "`Image`.`fk_chapter_id` = `NormalChapter`.`id` WHERE (`NormalChapter`.`id` = 10) ORDER BY `Image`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testChildParentSameType() {
    TestUtil::runAnonymous(true);

    $queryStr = "`ParentChapter`.`id` = 10";
    $query = new StringQuery('Chapter');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, ".
      "`Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` INNER JOIN `Chapter` AS `ParentChapter` ON ".
      "`Chapter`.`fk_chapter_id` = `ParentChapter`.`id` WHERE (`ParentChapter`.`id` = 10) ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testManyToMany() {
    TestUtil::runAnonymous(true);

    $queryStr = "`Publisher`.`name` LIKE '%Publisher 1%' AND `Author`.`name` = 'Author 2'";
    $query = new StringQuery('Publisher');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Publisher`.`id`, `Publisher`.`name`, ".
      "`Publisher`.`created`, `Publisher`.`creator`, `Publisher`.`modified`, `Publisher`.`last_editor` ".
      "FROM `Publisher` INNER JOIN `NMPublisherAuthor` ON `NMPublisherAuthor`.`fk_publisher_id` = `Publisher`.`id` ".
      "INNER JOIN `Author` ON `Author`.`id` = `NMPublisherAuthor`.`fk_author_id` ".
      "WHERE (`Publisher`.`name` LIKE '%Publisher 1%' AND `Author`.`name` = 'Author 2') ORDER BY `Publisher`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  /**
   * @expectedException wcmf\lib\core\IllegalArgumentException
   */
  public function testAmbiguousRelation() {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Image`.`file` = 'image.jpg'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "INNER JOIN `Image` AS `NormalImage` ON `NormalImage`.`fk_chapter_id` = `Chapter`.`id` INNER JOIN `Image` AS `TitleImage` ON ".
      "`TitleImage`.`fk_titlechapter_id` = `Chapter`.`id` WHERE (`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testDifferentRoles() {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`filename` = 'image.jpg' ".
      "AND `TitleImage`.`filename` = 'title_image.jpg'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor` FROM `Author` INNER JOIN `Chapter` ON `Chapter`.`fk_author_id` = `Author`.`id` ".
      "INNER JOIN `Image` AS `NormalImage` ON `NormalImage`.`fk_chapter_id` = `Chapter`.`id` INNER JOIN `Image` AS `TitleImage` ON ".
      "`TitleImage`.`fk_titlechapter_id` = `Chapter`.`id` WHERE (`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg') ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }
}
?>