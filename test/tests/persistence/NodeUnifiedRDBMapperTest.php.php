<?php

require_once(WCMF_BASE . "application/include/model/class.PageRDBMapper.php");
require_once(WCMF_BASE . "test/lib/WCMFTestCase.php");

class NodeUnifiedRDBMapperTest extends WCMFTestCase {

  public function testSelect() {
    $mapper = new PageRDBMapper(array('dbType' => 'mysql', 'dbHostName' => 'localhost', 
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf', 'dbPrefix' => ''));

    // condition
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'");    
    $expected = "SELECT `Page`.`id` AS `id`, `Page`.`fk_page_id` AS `fk_page_id`, `Page`.`fk_author_id` AS `fk_author_id`, ".
      "`Page`.`name` AS `name`, `Page`.`created` AS `created`, `Page`.`creator` AS `creator`, `Page`.`modified` AS `modified`, ".
      "`Page`.`last_editor` AS `last_editor`, `Page`.`sortkey` AS `sortkey`, 'Author' AS `ptype0`, 'Author' AS `prole0`, ".
      "`Page`.`fk_author_id` AS `pid0`, 'Page' AS `ptype1`, 'ParentPage' AS `prole1`, `Page`.`fk_page_id` AS `pid1` ".
      "FROM `Page` WHERE `Page`.`name` = 'Page 1' ORDER BY `Page`.`sortkey`;";
    $this->assertTrue($sql === $expected);

    // alias
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'", 
            "PageAlias");    
    $expected = "SELECT `PageAlias`.`id` AS `id`, `PageAlias`.`fk_page_id` AS `fk_page_id`, `PageAlias`.`fk_author_id` AS `fk_author_id`, ".
      "`PageAlias`.`name` AS `name`, `PageAlias`.`created` AS `created`, `PageAlias`.`creator` AS `creator`, `PageAlias`.`modified` AS `modified`, ".
      "`PageAlias`.`last_editor` AS `last_editor`, `PageAlias`.`sortkey` AS `sortkey`, 'Author' AS `ptype0`, 'Author' AS `prole0`, ".
      "`PageAlias`.`fk_author_id` AS `pid0`, 'Page' AS `ptype1`, 'ParentPage' AS `prole1`, `PageAlias`.`fk_page_id` AS `pid1` ".
      "FROM `Page` AS `PageAlias` WHERE `PageAlias`.`name` = 'Page 1' ORDER BY `PageAlias`.`sortkey`;";
    $this->assertTrue($sql === $expected);

    // order
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'", 
            null, $mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." ASC");
    $expected = "SELECT `Page`.`id` AS `id`, `Page`.`fk_page_id` AS `fk_page_id`, `Page`.`fk_author_id` AS `fk_author_id`, ".
      "`Page`.`name` AS `name`, `Page`.`created` AS `created`, `Page`.`creator` AS `creator`, `Page`.`modified` AS `modified`, ".
      "`Page`.`last_editor` AS `last_editor`, `Page`.`sortkey` AS `sortkey`, 'Author' AS `ptype0`, 'Author' AS `prole0`, ".
      "`Page`.`fk_author_id` AS `pid0`, 'Page' AS `ptype1`, 'ParentPage' AS `prole1`, `Page`.`fk_page_id` AS `pid1` ".
      "FROM `Page` WHERE `Page`.`name` = 'Page 1' ORDER BY `Page`.`name` ASC;";
    $this->assertTrue($sql === $expected);

    // attribs
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'", 
            null, null, array('id', 'name'));
    $expected = "SELECT `Page`.`id` AS `id`, `Page`.`name` AS `name`, 'Author' AS `ptype0`, 'Author' AS `prole0`, ".
      "`Page`.`fk_author_id` AS `pid0`, 'Page' AS `ptype1`, 'ParentPage' AS `prole1`, `Page`.`fk_page_id` AS `pid1` ".
      "FROM `Page` WHERE `Page`.`name` = 'Page 1' ORDER BY `Page`.`sortkey`;";
    $this->assertTrue($sql === $expected);

    // dbprefix
    $mapper = new PageRDBMapper(array('dbType' => 'mysql', 'dbHostName' => 'localhost', 
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf', 'dbPrefix' => 'WCMF_'));

    // condition
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('Page').".".$mapper->quoteIdentifier('name')." = 'Page 1'");    
    $expected = "SELECT `WCMF_Page`.`id` AS `id`, `WCMF_Page`.`fk_page_id` AS `fk_page_id`, `WCMF_Page`.`fk_author_id` AS `fk_author_id`, ".
      "`WCMF_Page`.`name` AS `name`, `WCMF_Page`.`created` AS `created`, `WCMF_Page`.`creator` AS `creator`, `WCMF_Page`.`modified` AS `modified`, ".
      "`WCMF_Page`.`last_editor` AS `last_editor`, `WCMF_Page`.`sortkey` AS `sortkey`, 'Author' AS `ptype0`, 'Author' AS `prole0`, ".
      "`WCMF_Page`.`fk_author_id` AS `pid0`, 'Page' AS 'ptype1', 'ParentPage' AS `prole1`, `WCMF_Page`.`fk_page_id` AS `pid1` ".
      "FROM `WCMF_Page` WHERE `WCMF_Page`.`name` = 'Page 1' ORDER BY `WCMF_Page`.`sortkey`;";
    $this->assertTrue($sql === $expected);

    
    $mapper = new UserRDBRDBMapper(array('dbType' => 'mysql', 'dbHostName' => 'localhost', 
        'dbUserName' => 'root', 'dbPassword' => '', 'dbName' => 'wcmf', 'dbPrefix' => ''));

    // condition
    $sql = $mapper->getSelectSQL($mapper->quoteIdentifier('User').".".$mapper->quoteIdentifier('name')." = 'Page 1'");    
    $expected = "SELECT `WCMF_Page`.`id` AS `id`, `WCMF_Page`.`fk_page_id` AS `fk_page_id`, `WCMF_Page`.`fk_author_id` AS `fk_author_id`, ".
      "`WCMF_Page`.`name` AS `name`, `WCMF_Page`.`created` AS `created`, `WCMF_Page`.`creator` AS `creator`, `WCMF_Page`.`modified` AS `modified`, ".
      "`WCMF_Page`.`last_editor` AS `last_editor`, `WCMF_Page`.`sortkey` AS `sortkey`, `Author` AS `ptype0`, `Author` AS `prole0`, ".
      "`WCMF_Page`.`fk_author_id` AS `pid0`, `WCMF_Page` AS `ptype1`, `ParentPage` AS `prole1`, `WCMF_Page`.`fk_page_id` AS `pid1` ".
      "FROM `WCMF_Page` WHERE `WCMF_Page`.`name` = 'Page 1' ORDER BY `WCMF_Page`.`sortkey`;";
    $this->assertTrue($sql === $expected);

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