<?php

require_once(WCMF_BASE . "application/include/model/class.PageRDBMapper.php");
require_once(WCMF_BASE . "application/include/model/class.AuthorRDBMapper.php");
require_once(WCMF_BASE . "application/include/model/class.ImageRDBMapper.php");
require_once(WCMF_BASE . "application/include/model/class.DocumentRDBMapper.php");
require_once(WCMF_BASE . "test/lib/WCMFTestCase.php");

class NodeUnifiedRDBMapperTest extends WCMFTestCase {

  protected $dbParams;

  protected function setUp()
  {
    $this->dbParams = array('dbType' => 'mysql', 'dbHostName' => 'localhost',
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf_newroles', 'dbPrefix' => '');
  }

  public function testSelectSQL() {
    $mapper = new PageRDBMapper($this->dbParams);
    $criteria = new Criteria('Page', 'name', '=', 'Page 1');

    // condition 1
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL')->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // condition 2
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = ('Page 1')) ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // alias
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL', array(array($criteria), "PageAlias"))->__toString();
    $expected = "SELECT `PageAlias`.`id`, `PageAlias`.`fk_page_id`, `PageAlias`.`fk_author_id`, `PageAlias`.`name`, ".
      "`PageAlias`.`created`, `PageAlias`.`creator`, `PageAlias`.`modified`, `PageAlias`.`last_editor`, ".
      "`PageAlias`.`sortkey`, `Author`.`name` AS `author_name` FROM `Page` AS `PageAlias` ".
      "LEFT JOIN `Author` ON `PageAlias`.`fk_author_id`=`Author`.`id` WHERE (`PageAlias`.`name` = ('Page 1')) ".
      "ORDER BY `PageAlias`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // order 1
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, array("name ASC")))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = ('Page 1')) ".
      "ORDER BY `Page`.`name` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // order 2
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, array("Page.name ASC")))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = ('Page 1')) ".
      "ORDER BY `Page`.`name` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // attribs 1
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name')))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`name` FROM `Page` WHERE (`Page`.`name` = ('Page 1')) ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // attribs 2
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, null, array('id', 'name', 'author_name')))->__toString();
    $expected = "SELECT `Page`.`id`, `Page`.`name`, `Author`.`name` AS `author_name` FROM `Page` ".
      "LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = ('Page 1')) ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // attribs 3
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL',
            array(array($criteria), null, null, array()))->__toString();
    $expected = "SELECT `Page`.`id` FROM `Page` WHERE (`Page`.`name` = ('Page 1')) ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // dbprefix
    $this->dbParams['dbPrefix'] = 'WCMF_';
    $mapper = new PageRDBMapper($this->dbParams);

    // condition
    $sql = $this->callProtectedMethod($mapper, 'getSelectSQL', array(array($criteria)))->__toString();
    $expected = "SELECT `WCMF_Page`.`id`, `WCMF_Page`.`fk_page_id`, `WCMF_Page`.`fk_author_id`, `WCMF_Page`.`name`, ".
      "`WCMF_Page`.`created`, `WCMF_Page`.`creator`, `WCMF_Page`.`modified`, `WCMF_Page`.`last_editor`, `WCMF_Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `WCMF_Page` LEFT JOIN `Author` ON `WCMF_Page`.`fk_author_id`=`Author`.`id` ".
      "WHERE (`WCMF_Page`.`name` = ('Page 1')) ORDER BY `WCMF_Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);
  }

  public function testRelationSQL() {
    $mapper = new PageRDBMapper($this->dbParams);

    $page = new Page(new ObjectId('Page', array(1)));
    $page->setFkAuthorId(12);

    // parent (pk only)
    $relationDescription = $mapper->getRelation('Author');
    $otherMapper = new AuthorRDBMapper($this->dbParams);
    $sql = $this->callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), array()))->__toString();
    $expected = "SELECT `Author`.`id` FROM `Author` WHERE (`Author`.`id`= 12)";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // parent (complets)
    $sql = $this->callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole()))->__toString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, ".
      "`Author`.`modified`, `Author`.`last_editor`, `Author`.`sortkey` FROM `Author` WHERE (`Author`.`id`= 12)";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // child (pk only)
    $relationDescription = $mapper->getRelation('NormalImage');
    $otherMapper = new ImageRDBMapper($this->dbParams);
    $sql = $this->callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), array()))->__toString();
    $expected = "SELECT `Image`.`id` FROM `Image` WHERE (`Image`.`fk_page_id`= 1)";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // child (complete)
    $sql = $this->callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole()))->__toString();
    $expected = "SELECT `Image`.`id`, `Image`.`fk_page_id`, `Image`.`fk_titlepage_id`, `Image`.`file`, ".
      "`Image`.`created`, `Image`.`creator`, `Image`.`modified`, `Image`.`last_editor` ".
      "FROM `Image` WHERE (`Image`.`fk_page_id`= 1)";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // many to many (pk only)
    $relationDescription = $mapper->getRelation('Document');
    $otherMapper = new DocumentRDBMapper($this->dbParams);
    $sql = $this->callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole(), array()))->__toString();
    $expected = "SELECT `Document`.`id` FROM `Document` INNER JOIN `NMPageDocument` ON ".
      "`NMPageDocument`.`fk_document_id`=`Document`.`id` WHERE (`NMPageDocument`.`fk_page_id`= 1)";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // many to many (complete)
    $sql = $this->callProtectedMethod($otherMapper, 'getRelationSelectSQL',
            array(PersistentObjectProxy::fromObject($page), $relationDescription->getThisRole()))->__toString();
    $expected = "SELECT `Document`.`id`, `Document`.`title`, `Document`.`created`, `Document`.`creator`, ".
      "`Document`.`modified`, `Document`.`last_editor`, `Document`.`sortkey` FROM `Document` ".
      "INNER JOIN `NMPageDocument` ON `NMPageDocument`.`fk_document_id`=`Document`.`id` ".
      "WHERE (`NMPageDocument`.`fk_page_id`= 1)";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);
  }

  public function testDisassociateSQL() {
    $mapper = new PageRDBMapper($this->dbParams);

    $operations = $this->callProtectedMethod($mapper, 'getChildrenDisassociateSQL',
            array(new ObjectId('Page', 1)));
    $this->assertTrue(sizeof($operations) == 4);

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertTrue($str == 'UpdateOperation:type=Image,values=(fk_titlepage_id=),criteria=(Image.fk_titlepage_id = 1)');

    $op = $operations[1];
    $str = $op->__toString();
    $this->assertTrue($str == 'UpdateOperation:type=Image,values=(fk_page_id=),criteria=(Image.fk_page_id = 1)');

    $op = $operations[2];
    $str = $op->__toString();
    $this->assertTrue($str == 'DeleteOperation:type=NMPageDocument,values=(),criteria=(NMPageDocument.fk_page_id = 1)');

    $op = $operations[3];
    $str = $op->__toString();
    $this->assertTrue($str == 'UpdateOperation:type=Page,values=(fk_page_id=),criteria=(Page.fk_page_id = 1)');
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

    $this->callProtectedMethod($mapper, 'prepareForStorage', array($page));
    $operations = $this->callProtectedMethod($mapper, 'getInsertSQL', array($page));
    $this->assertTrue(sizeof($operations) == 1);

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertTrue($str == 'InsertOperation:type=Page,values=(id=1,fk_page_id=3,fk_author_id=2,name=Page 1,created=2010-02-21,creator=admin),criteria=()');
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

    $this->callProtectedMethod($mapper, 'prepareForStorage', array($page));
    $operations = $this->callProtectedMethod($mapper, 'getUpdateSQL', array($page));
    $this->assertTrue(sizeof($operations) == 1);

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertTrue($str == 'UpdateOperation:type=Page,values=(id=1,fk_page_id=3,fk_author_id=2,name=Page 1,created=2010-02-21,creator=admin),criteria=(Page.id = 1)');
  }

  public function testDeleteSQL() {
    $mapper = new PageRDBMapper($this->dbParams);

    $operations = $this->callProtectedMethod($mapper, 'getDeleteSQL', array(new ObjectId('Page', 1)));
    $this->assertTrue(sizeof($operations) == 1);

    $op = $operations[0];
    $str = $op->__toString();
    $this->assertTrue($str == 'DeleteOperation:type=Page,values=(),criteria=(Page.id = 1)');
  }
}

?>