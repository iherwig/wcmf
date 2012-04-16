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
use wcmf\lib\persistence\PersistenceFacade;

/**
 * NodeUtilTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUtilTest extends \PHPUnit_Framework_TestCase {

  public function testGetConnection() {
    TestUtil::runAnonymous(true);

    $paths = NodeUtil::getConnections('Author', null, 'Image', 'child');
    $this->assertEquals(2, sizeof($paths));
    $endRoles = array($paths[0]->getEndRole(), $paths[1]->getEndRole());
    $this->assertContains('TitleImage', $endRoles);
    $this->assertContains('NormalImage', $endRoles);

    $paths = NodeUtil::getConnections('Author', null, 'Image', 'all');
    $this->assertEquals(2, sizeof($paths));
    $endRoles = array($paths[0]->getEndRole(), $paths[1]->getEndRole());
    $this->assertContains('TitleImage', $endRoles);
    $this->assertContains('NormalImage', $endRoles);

    $paths = NodeUtil::getConnections('Image', 'Author', null, 'parent');
    $this->assertEquals(2, sizeof($paths));
    $startRoles = array($paths[0]->getStartRole(), $paths[1]->getStartRole());
    $this->assertContains('TitleImage', $startRoles);
    $this->assertContains('NormalImage', $startRoles);

    $paths = NodeUtil::getConnections('Page', null, 'Page', 'all');
    $this->assertEquals(2, sizeof($paths));
    $endRoles = array($paths[0]->getEndRole(), $paths[1]->getEndRole());
    $this->assertContains('ChildPage', $endRoles);
    $this->assertContains('ParentPage', $endRoles);

    $paths = NodeUtil::getConnections('Page', null, 'Page', 'parent');
    $this->assertEquals(1, sizeof($paths));
    $this->assertEquals('ParentPage', $paths[0]->getEndRole());

    $paths = NodeUtil::getConnections('Page', 'ParentPage', null, 'parent');
    $this->assertEquals(1, sizeof($paths));
    $this->assertEquals('ParentPage', $paths[0]->getEndRole());

    $paths = NodeUtil::getConnections('Page', 'ChildPage', null, 'child');
    $this->assertEquals(1, sizeof($paths));
    $this->assertEquals('ChildPage', $paths[0]->getEndRole());

    $paths = NodeUtil::getConnections('Page', 'ChildPage', null, 'parent');
    $this->assertEquals(0, sizeof($paths));

    $paths = NodeUtil::getConnections('Page', 'Author', null, 'parent');
    $this->assertEquals(1, sizeof($paths));
    $this->assertEquals('Author', $paths[0]->getEndRole());

    $paths = NodeUtil::getConnections('Document', null, 'Page', 'child');
    $this->assertEquals(1, sizeof($paths));
    $this->assertEquals('Page', $paths[0]->getEndRole());

    $paths = NodeUtil::getConnections('Page', null, 'Document', 'child');
    $this->assertEquals(1, sizeof($paths));
    $this->assertEquals('Document', $paths[0]->getEndRole());

    TestUtil::runAnonymous(false);
  }

  public function testGetQueryCondition() {
    TestUtil::runAnonymous(true);

    // Page -> NormalImage
    $node = ObjectFactory::getInstance('persistenceFacade')->create('Page');
    $node->setOID(new ObjectId('Page', 10));
    $condition = NodeUtil::getRelationQueryCondition($node, 'NormalImage');
    $this->assertEquals('(`NormalPage`.`id` = 10)', $condition);
    // test with query
    $query = new StringQuery('Image');
    $query->setConditionString($condition);
    $sql = $query->getQueryString();
    $expected = "SELECT `Image`.`id`, `Image`.`fk_page_id`, `Image`.`fk_titlepage_id`, `Image`.`file` AS `filename`, `Image`.`created`, ".
      "`Image`.`creator`, `Image`.`modified`, `Image`.`last_editor` FROM `Image` INNER JOIN `Page` AS `NormalPage` ON ".
      "`Image`.`fk_page_id` = `NormalPage`.`id` WHERE ((`NormalPage`.`id` = 10))";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // Page -> ParentPage
    $node = ObjectFactory::getInstance('persistenceFacade')->create('Page');
    $node->setOID(new ObjectId('Page', 10));
    $condition = NodeUtil::getRelationQueryCondition($node, 'ParentPage');
    $this->assertEquals('(`ChildPage`.`id` = 10)', $condition);
    // test with query
    $query = new StringQuery('Page');
    $query->setConditionString($condition);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `Page` AS `ChildPage` ON `ChildPage`.`fk_page_id` = `Page`.`id` ".
      "WHERE ((`ChildPage`.`id` = 10)) ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // Page -> ChildPage
    $node = ObjectFactory::getInstance('persistenceFacade')->create('Page');
    $node->setOID(new ObjectId('Page', 10));
    $condition = NodeUtil::getRelationQueryCondition($node, 'ChildPage');
    $this->assertEquals('(`ParentPage`.`id` = 10)', $condition);
    // test with query
    $query = new StringQuery('Page');
    $query->setConditionString($condition);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `Page` AS `ParentPage` ON `Page`.`fk_page_id` = `ParentPage`.`id` ".
      "WHERE ((`ParentPage`.`id` = 10)) ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }
}
?>