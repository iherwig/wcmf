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
namespace wcmf\test\tests\model;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\ObjectId;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * NodeUtilTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUtilTest extends DatabaseTestCase {

  protected function getDataSet() {
    return new ArrayDataSet([
    ]);
  }

  public function testGetConnection() {
    $paths1 = NodeUtil::getConnections('Author', null, 'Image', 'child');
    $this->assertEquals(2, sizeof($paths1));
    $endRoles1 = [$paths1[0]->getEndRole(), $paths1[1]->getEndRole()];
    $this->assertContains('TitleImage', $endRoles1);
    $this->assertContains('NormalImage', $endRoles1);

    $paths2 = NodeUtil::getConnections('Author', null, 'Image', 'all');
    $this->assertEquals(2, sizeof($paths2));
    $endRoles2 = [$paths2[0]->getEndRole(), $paths2[1]->getEndRole()];
    $this->assertContains('TitleImage', $endRoles2);
    $this->assertContains('NormalImage', $endRoles2);

    $paths3 = NodeUtil::getConnections('Image', 'Author', null, 'parent');
    $this->assertEquals(2, sizeof($paths3));
    $startRoles3 = [$paths3[0]->getStartRole(), $paths3[1]->getStartRole()];
    $this->assertContains('TitleImage', $startRoles3);
    $this->assertContains('NormalImage', $startRoles3);

    $paths4 = NodeUtil::getConnections('Chapter', null, 'Chapter', 'all');
    $this->assertEquals(2, sizeof($paths4));
    $endRoles4 = [$paths4[0]->getEndRole(), $paths4[1]->getEndRole()];
    $this->assertContains('ParentChapter', $endRoles4);
    $this->assertContains('SubChapter', $endRoles4);

    $paths5 = NodeUtil::getConnections('Chapter', null, 'Chapter', 'parent');
    $this->assertEquals(1, sizeof($paths5));
    $this->assertEquals('ParentChapter', $paths5[0]->getEndRole());

    $paths6 = NodeUtil::getConnections('Chapter', 'ParentChapter', null, 'parent');
    $this->assertEquals(1, sizeof($paths6));
    $this->assertEquals('ParentChapter', $paths6[0]->getEndRole());

    $paths7 = NodeUtil::getConnections('Chapter', 'SubChapter', null, 'child');
    $this->assertEquals(1, sizeof($paths7));
    $this->assertEquals('SubChapter', $paths7[0]->getEndRole());

    $paths8 = NodeUtil::getConnections('Chapter', 'SubChapter', null, 'parent');
    $this->assertEquals(0, sizeof($paths8));

    $paths9 = NodeUtil::getConnections('Chapter', 'Author', null, 'parent');
    $this->assertEquals(1, sizeof($paths9));
    $this->assertEquals('Author', $paths9[0]->getEndRole());

    $paths10 = NodeUtil::getConnections('Publisher', null, 'Author', 'child');
    $this->assertEquals(1, sizeof($paths10));
    $this->assertEquals('Author', $paths10[0]->getEndRole());

    $paths11 = NodeUtil::getConnections('Author', null, 'Publisher', 'child');
    $this->assertEquals(1, sizeof($paths11));
    $this->assertEquals('Publisher', $paths11[0]->getEndRole());
  }

  public function testGetQueryCondition() {
    // Chapter -> NormalImage
    $node1 = ObjectFactory::getInstance('persistenceFacade')->create('Chapter');
    $node1->setOID(new ObjectId('Chapter', 10));
    $condition1 = NodeUtil::getRelationQueryCondition($node1, 'NormalImage');
    $this->assertEquals($this->fixQueryQuotes("(`NormalChapter`.`id` = 10)", 'Image'), $condition1);
    // test with query
    $query1 = new StringQuery('Image', __CLASS__.__METHOD__."1");
    $query1->setConditionString($condition1);
    $sql1 = $query1->getQueryString();
    $expected1 = "SELECT DISTINCT `Image`.`id` AS `id`, `Image`.`fk_chapter_id` AS `fk_chapter_id`, `Image`.`fk_titlechapter_id` AS `fk_titlechapter_id`, ".
      "`Image`.`file` AS `filename`, `Image`.`created` AS `created`, ".
      "`Image`.`creator` AS `creator`, `Image`.`modified` AS `modified`, `Image`.`last_editor` AS `last_editor`, ".
      "`Image`.`sortkey_titlechapter` AS `sortkey_titlechapter`, `Image`.`sortkey_normalchapter` AS `sortkey_normalchapter`, `Image`.`sortkey` AS `sortkey` ".
      "FROM `Image` INNER JOIN `Chapter` AS `NormalChapter` ON ".
      "`Image`.`fk_chapter_id` = `NormalChapter`.`id` WHERE (`NormalChapter`.`id` = 10) ORDER BY `Image`.`sortkey` DESC";
    $this->assertEquals($this->fixQueryQuotes($expected1, 'Image'), str_replace("\n", "", $sql1));
    $this->executeSql('Image', $sql1);

    // Chapter -> ParentChapter
    $node2 = ObjectFactory::getInstance('persistenceFacade')->create('Chapter');
    $node2->setOID(new ObjectId('Chapter', 10));
    $condition2 = NodeUtil::getRelationQueryCondition($node2, 'ParentChapter');
    $this->assertEquals($this->fixQueryQuotes("(`SubChapter`.`id` = 10)", 'Chapter'), $condition2);
    // test with query
    $query2 = new StringQuery('Chapter', __CLASS__.__METHOD__."2");
    $query2->setConditionString($condition2);
    $sql2 = $query2->getQueryString();
    $expected2 = "SELECT DISTINCT `Chapter`.`id` AS `id`, `Chapter`.`fk_chapter_id` AS `fk_chapter_id`, `Chapter`.`fk_book_id` AS `fk_book_id`, `Chapter`.`fk_author_id` AS `fk_author_id`, ".
      "`Chapter`.`name` AS `name`, `Chapter`.`content` AS `content`, `Chapter`.`created` AS `created`, `Chapter`.`creator` AS `creator`, `Chapter`.`modified` AS `modified`, ".
      "`Chapter`.`last_editor` AS `last_editor`, `Chapter`.`sortkey_author` AS `sortkey_author`, `Chapter`.`sortkey_book` AS `sortkey_book`, `Chapter`.`sortkey_parentchapter` AS `sortkey_parentchapter`, ".
      "`Chapter`.`sortkey` AS `sortkey`, ".
      "`AuthorRef`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` AS `AuthorRef` ON `Chapter`.`fk_author_id`=`AuthorRef`.`id` ".
      "INNER JOIN `Chapter` AS `SubChapter` ON `SubChapter`.`fk_chapter_id` = `Chapter`.`id` ".
      "WHERE (`SubChapter`.`id` = 10) ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected2, 'Chapter'), str_replace("\n", "", $sql2));
    $this->executeSql('Chapter', $sql2);

    // Chapter -> SubChapter
    $node3 = ObjectFactory::getInstance('persistenceFacade')->create('Chapter');
    $node3->setOID(new ObjectId('Chapter', 10));
    $condition3 = NodeUtil::getRelationQueryCondition($node3, 'SubChapter');
    $this->assertEquals($this->fixQueryQuotes("(`ParentChapter`.`id` = 10)", 'Chapter'), $condition3);
    // test with query
    $query3 = new StringQuery('Chapter', __CLASS__.__METHOD__."3");
    $query3->setConditionString($condition3);
    $sql3 = $query3->getQueryString();
    $expected3 = "SELECT DISTINCT `Chapter`.`id` AS `id`, `Chapter`.`fk_chapter_id` AS `fk_chapter_id`, `Chapter`.`fk_book_id` AS `fk_book_id`, `Chapter`.`fk_author_id` AS `fk_author_id`, ".
      "`Chapter`.`name` AS `name`, `Chapter`.`content` AS `content`, `Chapter`.`created` AS `created`, `Chapter`.`creator` AS `creator`, `Chapter`.`modified` AS `modified`, ".
      "`Chapter`.`last_editor` AS `last_editor`, `Chapter`.`sortkey_author` AS `sortkey_author`, `Chapter`.`sortkey_book` AS `sortkey_book`, `Chapter`.`sortkey_parentchapter` AS `sortkey_parentchapter`, ".
      "`Chapter`.`sortkey` AS `sortkey`, ".
      "`AuthorRef`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` AS `AuthorRef` ON `Chapter`.`fk_author_id`=`AuthorRef`.`id` ".
      "INNER JOIN `Chapter` AS `ParentChapter` ON `Chapter`.`fk_chapter_id` = `ParentChapter`.`id` ".
      "WHERE (`ParentChapter`.`id` = 10) ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($this->fixQueryQuotes($expected3, 'Chapter'), str_replace("\n", "", $sql3));
    $this->executeSql('Chapter', $sql3);
  }
}
?>