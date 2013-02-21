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
namespace test\tests\model;

use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\ObjectId;

/**
 * NodeUtilTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUtilTest extends \PHPUnit_Framework_TestCase {

  public function testGetConnection() {
    TestUtil::runAnonymous(true);

    $paths1 = NodeUtil::getConnections('Author', null, 'Image', 'child');
    $this->assertEquals(2, sizeof($paths1));
    $endRoles1 = array($paths1[0]->getEndRole(), $paths1[1]->getEndRole());
    $this->assertContains('TitleImage', $endRoles1);
    $this->assertContains('NormalImage', $endRoles1);

    $paths2 = NodeUtil::getConnections('Author', null, 'Image', 'all');
    $this->assertEquals(2, sizeof($paths2));
    $endRoles2 = array($paths2[0]->getEndRole(), $paths2[1]->getEndRole());
    $this->assertContains('TitleImage', $endRoles2);
    $this->assertContains('NormalImage', $endRoles2);

    $paths3 = NodeUtil::getConnections('Image', 'Author', null, 'parent');
    $this->assertEquals(2, sizeof($paths3));
    $startRoles3 = array($paths3[0]->getStartRole(), $paths3[1]->getStartRole());
    $this->assertContains('TitleImage', $startRoles3);
    $this->assertContains('NormalImage', $startRoles3);

    $paths4 = NodeUtil::getConnections('Chapter', null, 'Chapter', 'all');
    $this->assertEquals(2, sizeof($paths4));
    $endRoles4 = array($paths4[0]->getEndRole(), $paths4[1]->getEndRole());
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

    TestUtil::runAnonymous(false);
  }

  public function testGetQueryCondition() {
    TestUtil::runAnonymous(true);

    // Chapter -> NormalImage
    $node1 = ObjectFactory::getInstance('persistenceFacade')->create('Chapter');
    $node1->setOID(new ObjectId('Chapter', 10));
    $condition1 = NodeUtil::getRelationQueryCondition($node1, 'NormalImage');
    $this->assertEquals('(`NormalChapter`.`id` = 10)', $condition1);
    // test with query
    $query1 = new StringQuery('Image');
    $query1->setConditionString($condition1);
    $sql1 = $query1->getQueryString();
    $expected1 = "SELECT `Image`.`id`, `Image`.`fk_chapter_id`, `Image`.`fk_titlechapter_id`, `Image`.`file` AS `filename`, `Image`.`created`, ".
      "`Image`.`creator`, `Image`.`modified`, `Image`.`last_editor`, `Image`.`sortkey_titlechapter`, `Image`.`sortkey_normalchapter`, `Image`.`sortkey` ".
      "FROM `Image` INNER JOIN `Chapter` AS `NormalChapter` ON ".
      "`Image`.`fk_chapter_id` = `NormalChapter`.`id` WHERE ((`NormalChapter`.`id` = 10)) ORDER BY `Image`.`sortkey` ASC";
    $this->assertEquals($expected1, str_replace("\n", "", $sql1));

    // Chapter -> ParentChapter
    $node2 = ObjectFactory::getInstance('persistenceFacade')->create('Chapter');
    $node2->setOID(new ObjectId('Chapter', 10));
    $condition2 = NodeUtil::getRelationQueryCondition($node2, 'ParentChapter');
    $this->assertEquals('(`SubChapter`.`id` = 10)', $condition2);
    // test with query
    $query2 = new StringQuery('Chapter');
    $query2->setConditionString($condition2);
    $sql2 = $query2->getQueryString();
    $expected2 = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `Chapter` AS `SubChapter` ON `SubChapter`.`fk_chapter_id` = `Chapter`.`id` ".
      "WHERE ((`SubChapter`.`id` = 10)) ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected2, str_replace("\n", "", $sql2));

    // Chapter -> SubChapter
    $node3 = ObjectFactory::getInstance('persistenceFacade')->create('Chapter');
    $node3->setOID(new ObjectId('Chapter', 10));
    $condition3 = NodeUtil::getRelationQueryCondition($node3, 'SubChapter');
    $this->assertEquals('(`ParentChapter`.`id` = 10)', $condition3);
    // test with query
    $query3 = new StringQuery('Chapter');
    $query3->setConditionString($condition3);
    $sql3 = $query3->getQueryString();
    $expected3 = "SELECT `Chapter`.`id`, `Chapter`.`fk_chapter_id`, `Chapter`.`fk_book_id`, `Chapter`.`fk_author_id`, `Chapter`.`name`, `Chapter`.`created`, ".
      "`Chapter`.`creator`, `Chapter`.`modified`, `Chapter`.`last_editor`, `Chapter`.`sortkey_author`, `Chapter`.`sortkey_book`, `Chapter`.`sortkey_parentchapter`, `Chapter`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Chapter` LEFT JOIN `Author` ON `Chapter`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `Chapter` AS `ParentChapter` ON `Chapter`.`fk_chapter_id` = `ParentChapter`.`id` ".
      "WHERE ((`ParentChapter`.`id` = 10)) ORDER BY `Chapter`.`sortkey` ASC";
    $this->assertEquals($expected3, str_replace("\n", "", $sql3));

    TestUtil::runAnonymous(false);
  }
}
?>