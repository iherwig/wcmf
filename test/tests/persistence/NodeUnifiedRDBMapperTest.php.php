<?php

require_once(WCMF_BASE . "application/include/model/class.PageRDBMapper.php");
require_once(WCMF_BASE . "test/lib/WCMFTestCase.php");

class NodeUnifiedRDBMapperTest extends WCMFTestCase {

  public function testSelect() {
    $mapper = new PageRDBMapper(array('dbType' => 'mysql', 'dbHostName' => 'localhost',
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf_newroles', 'dbPrefix' => ''));

    // condition
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'");
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // alias
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'",
            "PageAlias");
    $expected = "SELECT `PageAlias`.`id`, `PageAlias`.`fk_page_id`, `PageAlias`.`fk_author_id`, `PageAlias`.`name`, ".
      "`PageAlias`.`created`, `PageAlias`.`creator`, `PageAlias`.`modified`, `PageAlias`.`last_editor`, ".
      "`PageAlias`.`sortkey`, `Author`.`name` AS `author_name` FROM `Page` AS `PageAlias` ".
      "LEFT JOIN `Author` ON `PageAlias`.`fk_author_id`=`Author`.`id` WHERE (`PageAlias`.`name` = 'Page 1') ".
      "ORDER BY `PageAlias`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // order
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'",
            null, "Page.name ASC");
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, `Author`.`name` AS `author_name` ".
      "FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`name` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // attribs 1
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'",
            null, null, array('id', 'name'));
    $expected = "SELECT `Page`.`id`, `Page`.`name` FROM `Page` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // attribs 2
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'",
            null, null, array('id', 'name', 'author_name'));
    $expected = "SELECT `Page`.`id`, `Page`.`name`, `Author`.`name` AS `author_name` FROM `Page` ".
      "LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` WHERE (`Page`.`name` = 'Page 1') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    // dbprefix
    $mapper = new PageRDBMapper(array('dbType' => 'mysql', 'dbHostName' => 'localhost',
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf_newroles', 'dbPrefix' => 'WCMF_'));

    // condition
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'");
    $expected = "SELECT `WCMF_Page`.`id`, `WCMF_Page`.`fk_page_id`, `WCMF_Page`.`fk_author_id`, `WCMF_Page`.`name`, ".
      "`WCMF_Page`.`created`, `WCMF_Page`.`creator`, `WCMF_Page`.`modified`, `WCMF_Page`.`last_editor`, `WCMF_Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `WCMF_Page` LEFT JOIN `Author` ON `WCMF_Page`.`fk_author_id`=`Author`.`id` ".
      "WHERE (`WCMF_Page`.`name` = 'Page 1') ORDER BY `WCMF_Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);
  }

  public function testRelation() {
    // parent

    // child

    // many to many

  }

  public function testDisassociate() {
  }

  public function testInsert() {
  }

  public function testUpdate() {
  }

  public function testDelete() {
  }

  public function testReference() {
  }
}

?>