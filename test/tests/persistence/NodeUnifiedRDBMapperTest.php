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

use new_roles\app\model\Author;
use new_roles\app\model\AuthorRDBMapper;
use new_roles\app\model\DocumentRDBMapper;
use new_roles\app\model\ImageRDBMapper;
use new_roles\app\model\Page;
use new_roles\app\model\PageRDBMapper;

use test\lib\TestUtil;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;

/**
 * NodeUnifiedRDBMapperTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUnifiedRDBMapperTest extends \PHPUnit_Framework_TestCase {

  protected $dbParams;

  protected function setUp() {
    $this->dbParams = array('dbType' => 'mysql', 'dbHostName' => 'localhost',
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf_newroles', 'dbPrefix' => '');
  }

  public function testSelectSQL() {
    $mapper = new PageRDBMapper($this->dbParams);
    $criteria = new Criteria('Page', 'name', '=', 'Page 1');

    // condition 1
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL')->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // condition 2
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // alias
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL', array(array($criteria), "PageAlias"))->__toString();
    $expected = "SELECT `PageAlias`.`id`, `PageAlias`.`fk_page_id`, `PageAlias`.`fk_author_id`, `PageAlias`.`name`, ".
      "`PageAlias`.`created`, `PageAlias`.`creator`, `PageAlias`.`modified`, `PageAlias`.`last_editor`, ".
      "`PageAlias`.`sortkey_author`, `PageAlias`.`sortkey_page`, `PageAlias`.`sortkey`, `Author`.`name` AS `author_name` FROM `Page` AS `PageAlias` ".
      "LEFT JOIN `Author` ON `PageAlias`.`fk_author_id`=`Author`.`id` WHERE (`PageAlias`.`name` = 'Page 1') ".
      "ORDER BY `PageAlias`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // order 1
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, array("name ASC")))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // order 2
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, array("Page.name ASC")))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // attribs 1
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name')))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`name` FROM `Page` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // attribs 2
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name', 'author_name')))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`name`, `Author`.`name` AS `author_name` FROM `Page` ".
      "LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // attribs 3
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, null, array()))->__toString();
    $expected = "SELECT `Page`.`id` FROM `Page` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // dbprefix
    $this->dbParams['dbPrefix'] = 'WCMF_';
    $mapper = new PageRDBMapper($this->dbParams);

    // condition
    $sql = TestUtil::callProtectedMethod($mapper, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `WCMF_Page`.`id`, `WCMF_Page`.`fk_page_id`, `WCMF_Page`.`fk_author_id`, `WCMF_Page`.`name`, ".
      "`WCMF_Page`.`created`, `WCMF_Page`.`creator`, `WCMF_Page`.`modified`, `WCMF_Page`.`last_editor`, `WCMF_Page`.`sortkey_author`, `WCMF_Page`.`sortkey_page`, `WCMF_Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `WCMF_Page` LEFT JOIN `Author` ON `WCMF_Page`.`fk_author_id`=`Author`.`id` ".
      "WHERE (`WCMF_Page`.`name` = 'Page 1') ORDER BY `WCMF_Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));
  }

  public function testRelationSQL() {
    $mapper = new PageRDBMapper($this->dbParams);

    $page = new Page(new ObjectId('Page', array(1)));
    $page->setFkAuthorId(12);

    // parent (pk only)
    $relationDescription = $mapper->getRelation('Author');
    $otherMapper = new AuthorRDBMapper($this->dbParams);
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), null, null, array()))->__toString();
    $expected = "SELECT `Author`.`id` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // parent (complete)
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole()))->__toString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor`, `Author`.`sortkey` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // parent (order)
    $relationDescription = $mapper->getRelation('Author');
    $otherMapper = new AuthorRDBMapper($this->dbParams);
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), null, array('name')))->__toString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor`, `Author`.`sortkey` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // parent (criteria)
    $criteria = new Criteria('Author', 'name', '=', 'Unknown');
    $relationDescription = $mapper->getRelation('Author');
    $otherMapper = new AuthorRDBMapper($this->dbParams);
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), array($criteria)))->__toString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor`, `Author`.`sortkey` FROM `Author` WHERE (`Author`.`id`= 12) AND (`Author`.`name` = 'Unknown') ".
      "ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // child (pk only)
    $relationDescription = $mapper->getRelation('NormalImage');
    $otherMapper = new ImageRDBMapper($this->dbParams);
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), null, null, array()))->__toString();
    $expected = "SELECT `Image`.`id` FROM `Image` WHERE (`Image`.`fk_page_id`= 1)";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // child (complete)
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole()))->__toString();
    $expected = "SELECT `Image`.`id`, `Image`.`fk_page_id`, `Image`.`fk_titlepage_id`, `Image`.`file` AS `filename`, ".
      "`Image`.`created`, `Image`.`creator`, `Image`.`modified`, `Image`.`last_editor` ".
      "FROM `Image` WHERE (`Image`.`fk_page_id`= 1)";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // many to many (pk only)
    $relationDescription = $mapper->getRelation('Document');
    $otherMapper = new DocumentRDBMapper($this->dbParams);
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), null, null, array()))->__toString();
    $expected = "SELECT `Document`.`id`, `NMPageDocument`.`sortkey_page` FROM `Document` INNER JOIN `NMPageDocument` ON ".
      "`NMPageDocument`.`fk_document_id`=`Document`.`id` WHERE (`NMPageDocument`.`fk_page_id`= 1) ORDER BY `NMPageDocument`.`sortkey_page` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    // many to many (complete)
    $sql = TestUtil::callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole()))->__toString();
    $expected = "SELECT `Document`.`id`, `Document`.`title`, `Document`.`created`, `Document`.`creator`, ".
      "`Document`.`modified`, `Document`.`last_editor`, `NMPageDocument`.`sortkey_page` FROM `Document` ".
      "INNER JOIN `NMPageDocument` ON `NMPageDocument`.`fk_document_id`=`Document`.`id` ".
      "WHERE (`NMPageDocument`.`fk_page_id`= 1) ORDER BY `NMPageDocument`.`sortkey_page` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));
  }

  public function testInsertSQL() {
    $mapper = new PageRDBMapper($this->dbParams);

    $page = new Page(new ObjectId('Page', 1));
    $page->setValue('name', 'Page 1');
    $page->setValue('created', '2010-02-21');
    $page->setValue('creator', 'admin');

    $author = new Author(new ObjectId('Author', 2));
    $author->addNode($page, 'Page');
    $page2 = new Page(new ObjectId('Page', 3));
    $page2->addNode($page, 'ChildPage');

    TestUtil::callProtectedMethod($mapper, 'prepareForStorage', array($page));
    $operations = TestUtil::callProtectedMethod($mapper, 'getInsertSQL', array($page));
    $this->assertEquals(1, sizeof($operations));

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertEquals('InsertOperation:type=Page,values=(id=1,fk_page_id=3,fk_author_id=2,name=Page 1,created=2010-02-21,creator=admin),criteria=()', $str);
  }

  public function testUpdateSQL() {
    $mapper = new PageRDBMapper($this->dbParams);

    $page = new Page(new ObjectId('Page', 1));
    $page->setValue('name', 'Page 1');
    $page->setValue('created', '2010-02-21');
    $page->setValue('creator', 'admin');

    $author = new Author(new ObjectId('Author', 2));
    $author->addNode($page, 'Page');
    $page2 = new Page(new ObjectId('Page', 3));
    $page2->addNode($page, 'ChildPage');

    TestUtil::callProtectedMethod($mapper, 'prepareForStorage', array($page));
    $operations = TestUtil::callProtectedMethod($mapper, 'getUpdateSQL', array($page));
    $this->assertEquals(1, sizeof($operations));

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertEquals('UpdateOperation:type=Page,values=(id=1,fk_page_id=3,fk_author_id=2,name=Page 1,created=2010-02-21,creator=admin),criteria=([AND] Page.id = 1)', $str);
  }

  public function testDeleteSQL() {
    $mapper = new PageRDBMapper($this->dbParams);

    $operations = TestUtil::callProtectedMethod($mapper, 'getDeleteSQL', array(new ObjectId('Page', 1)));
    $this->assertEquals(1, sizeof($operations));

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertEquals('DeleteOperation:type=Page,values=(),criteria=([AND] Page.id = 1)', $str);
  }
}
?>