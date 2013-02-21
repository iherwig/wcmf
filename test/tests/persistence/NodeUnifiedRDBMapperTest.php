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

use testapp\application\model\Author;
use testapp\application\model\AuthorRDBMapper;
use testapp\application\model\DocumentRDBMapper;
use testapp\application\model\ImageRDBMapper;
use testapp\application\model\Page;
use testapp\application\model\PageRDBMapper;

use test\lib\TestUtil;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObjectProxy;

/**
 * NodeUnifiedRDBMapperTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeUnifiedRDBMapperTest extends \PHPUnit_Framework_TestCase {

  protected $dbParams;

  protected function setUp() {
    $this->dbParams = array('dbType' => 'mysql', 'dbHostName' => 'localhost',
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf_testapp', 'dbPrefix' => '');
  }

  public function testSelectSQL() {
    $mapper1 = new PageRDBMapper();
    $mapper1->setConnectionParams($this->dbParams);
    $criteria = new Criteria('Page', 'name', '=', 'Page 1');

    // condition 1
    $sql1 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL')->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_parentpage`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql1));

    // condition 2
    $sql2 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_parentpage`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql2));

    // alias
    $sql3 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL', array(array($criteria), "PageAlias"))->__toString();
    $expected = "SELECT `PageAlias`.`id`, `PageAlias`.`fk_page_id`, `PageAlias`.`fk_author_id`, `PageAlias`.`name`, ".
      "`PageAlias`.`created`, `PageAlias`.`creator`, `PageAlias`.`modified`, `PageAlias`.`last_editor`, ".
      "`PageAlias`.`sortkey_author`, `PageAlias`.`sortkey_parentpage`, `PageAlias`.`sortkey`, `Author`.`name` AS `author_name` FROM `Page` AS `PageAlias` ".
      "LEFT JOIN `Author` ON `PageAlias`.`fk_author_id`=`Author`.`id` WHERE (`PageAlias`.`name` = 'Page 1') ".
      "ORDER BY `PageAlias`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql3));

    // order 1
    $sql4 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, array("name ASC")))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_parentpage`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql4));

    // order 2
    $sql5 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, array("Page.name ASC")))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_parentpage`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`name` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql5));

    // attribs 1
    $sql6 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name')))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`name` FROM `Page` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql6));

    // attribs 2
    $sql7 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name', 'author_name')))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`name`, `Author`.`name` AS `author_name` FROM `Page` ".
      "LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql7));

    // attribs 3
    $sql8 = TestUtil::callProtectedMethod($mapper1, 'getSelectSQL',
            array(array($criteria), null, null, array()))->__toString();
    $expected = "SELECT `Page`.`id` FROM `Page` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql8));

    // dbprefix
    $this->dbParams['dbPrefix'] = 'WCMF_';
    $mapper2 = new PageRDBMapper();
    $mapper2->setConnectionParams($this->dbParams);

    // condition
    $sql9 = TestUtil::callProtectedMethod($mapper2, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `WCMF_Page`.`id`, `WCMF_Page`.`fk_page_id`, `WCMF_Page`.`fk_author_id`, `WCMF_Page`.`name`, ".
      "`WCMF_Page`.`created`, `WCMF_Page`.`creator`, `WCMF_Page`.`modified`, `WCMF_Page`.`last_editor`, `WCMF_Page`.`sortkey_author`, `WCMF_Page`.`sortkey_parentpage`, `WCMF_Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `WCMF_Page` LEFT JOIN `Author` ON `WCMF_Page`.`fk_author_id`=`Author`.`id` ".
      "WHERE (`WCMF_Page`.`name` = 'Page 1') ORDER BY `WCMF_Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql9));
  }

  public function testRelationSQL() {
    $mapper = new PageRDBMapper();
    $mapper->setConnectionParams($this->dbParams);

    $page = new Page(new ObjectId('Page', array(1)));
    $page->setFkAuthorId(12);

    // parent (pk only)
    $relationDescription1 = $mapper->getRelation('Author');
    $otherMapper1 = new AuthorRDBMapper();
    $otherMapper1->setConnectionParams($this->dbParams);
    $sql1 = TestUtil::callProtectedMethod($otherMapper1, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription1->getThisRole(), null, null, array()))->__toString();
    $expected1 = "SELECT `Author`.`id` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected1, str_replace("\n", "", $sql1));

    // parent (complete)
    $relationDescription2 = $mapper->getRelation('Author');
    $otherMapper2 = new AuthorRDBMapper();
    $otherMapper2->setConnectionParams($this->dbParams);
    $sql2 = TestUtil::callProtectedMethod($otherMapper2, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription2->getThisRole()))->__toString();
    $expected2 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected2, str_replace("\n", "", $sql2));

    // parent (order)
    $relationDescription3 = $mapper->getRelation('Author');
    $otherMapper3 = new AuthorRDBMapper();
    $otherMapper3->setConnectionParams($this->dbParams);
    $sql3 = TestUtil::callProtectedMethod($otherMapper3, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription3->getThisRole(), null, array('name')))->__toString();
    $expected3 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id`= 12) ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected3, str_replace("\n", "", $sql3));

    // parent (criteria)
    $criteria4 = new Criteria('Author', 'name', '=', 'Unknown');
    $relationDescription4 = $mapper->getRelation('Author');
    $otherMapper4 = new AuthorRDBMapper();
    $otherMapper4->setConnectionParams($this->dbParams);
    $sql4 = TestUtil::callProtectedMethod($otherMapper4, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription4->getThisRole(), array($criteria4)))->__toString();
    $expected4 = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor` FROM `Author` WHERE (`Author`.`id`= 12) AND (`Author`.`name` = 'Unknown') ".
      "ORDER BY `Author`.`name` ASC";
    $this->assertEquals($expected4, str_replace("\n", "", $sql4));

    // child (pk only)
    $relationDescription5 = $mapper->getRelation('NormalImage');
    $otherMapper5 = new ImageRDBMapper();
    $otherMapper5->setConnectionParams($this->dbParams);
    $sql5 = TestUtil::callProtectedMethod($otherMapper5, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription5->getThisRole(), null, null, array()))->__toString();
    $expected5 = "SELECT `Image`.`id` FROM `Image` WHERE (`Image`.`fk_page_id`= 1) ORDER BY `Image`.`sortkey_normalpage` ASC";
    $this->assertEquals($expected5, str_replace("\n", "", $sql5));

    // child (complete)
    $relationDescription6 = $mapper->getRelation('NormalImage');
    $otherMapper6 = new ImageRDBMapper();
    $otherMapper6->setConnectionParams($this->dbParams);
    $sql6 = TestUtil::callProtectedMethod($otherMapper6, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription6->getThisRole()))->__toString();
    $expected6 = "SELECT `Image`.`id`, `Image`.`fk_page_id`, `Image`.`fk_titlepage_id`, `Image`.`file` AS `filename`, ".
      "`Image`.`created`, `Image`.`creator`, `Image`.`modified`, `Image`.`last_editor`, ".
      "`Image`.`sortkey_titlepage`, `Image`.`sortkey_normalpage`, `Image`.`sortkey` ".
      "FROM `Image` WHERE (`Image`.`fk_page_id`= 1) ORDER BY `Image`.`sortkey_normalpage` ASC";
    $this->assertEquals($expected6, str_replace("\n", "", $sql6));

    // many to many (pk only)
    $relationDescription7 = $mapper->getRelation('Document');
    $otherMapper7 = new DocumentRDBMapper();
    $otherMapper7->setConnectionParams($this->dbParams);
    $sql7 = TestUtil::callProtectedMethod($otherMapper7, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription7->getThisRole(), null, null, array()))->__toString();
    $expected7 = "SELECT `Document`.`id`, `NMPageDocument`.`sortkey_page` FROM `Document` INNER JOIN `NMPageDocument` ON ".
      "`NMPageDocument`.`fk_document_id`=`Document`.`id` WHERE (`NMPageDocument`.`fk_page_id`= 1) ORDER BY `NMPageDocument`.`sortkey_page` ASC";
    $this->assertEquals($expected7, str_replace("\n", "", $sql7));

    // many to many (complete)
    $relationDescription8 = $mapper->getRelation('Document');
    $otherMapper8 = new DocumentRDBMapper();
    $otherMapper8->setConnectionParams($this->dbParams);
    $sql8 = TestUtil::callProtectedMethod($otherMapper8, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription8->getThisRole()))->__toString();
    $expected8 = "SELECT `Document`.`id`, `Document`.`title`, `Document`.`created`, `Document`.`creator`, ".
      "`Document`.`modified`, `Document`.`last_editor`, `NMPageDocument`.`sortkey_page` FROM `Document` ".
      "INNER JOIN `NMPageDocument` ON `NMPageDocument`.`fk_document_id`=`Document`.`id` ".
      "WHERE (`NMPageDocument`.`fk_page_id`= 1) ORDER BY `NMPageDocument`.`sortkey_page` ASC";
    $this->assertEquals($expected8, str_replace("\n", "", $sql8));
  }

  public function testInsertSQL() {
    $mapper = new PageRDBMapper();
    $mapper->setConnectionParams($this->dbParams);

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
    $this->assertEquals('wcmf\lib\persistence\InsertOperation:type=Page,values=(id=1,fk_page_id=3,fk_author_id=2,name=Page 1,created=2010-02-21,creator=admin),criteria=()', $str);
  }

  public function testUpdateSQL() {
    $mapper = new PageRDBMapper();
    $mapper->setConnectionParams($this->dbParams);

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
    $this->assertEquals('wcmf\lib\persistence\UpdateOperation:type=Page,values=(id=1,fk_page_id=3,fk_author_id=2,name=Page 1,created=2010-02-21,creator=admin),criteria=([AND] Page.id = 1)', $str);
  }

  public function testDeleteSQL() {
    $mapper = new PageRDBMapper();
    $mapper->setConnectionParams($this->dbParams);

    $operations = TestUtil::callProtectedMethod($mapper, 'getDeleteSQL', array(new ObjectId('Page', 1)));
    $this->assertEquals(1, sizeof($operations));

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertEquals('wcmf\lib\persistence\DeleteOperation:type=Page,values=(),criteria=([AND] Page.id = 1)', $str);
  }
}
?>