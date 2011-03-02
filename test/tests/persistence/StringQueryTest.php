<?php
require_once(WCMF_BASE."wcmf/lib/model/class.StringQuery.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class StringQueryTest extends WCMFTestCase
{
  public function testSimple()
  {
    $this->runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, `Author`.`last_editor`, ".
      "`Author`.`sortkey` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    $this->runAnonymous(false);
  }

  public function testParentChild()
  {
    $this->runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Page`.`name` LIKE 'Page 1%'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Page` ON `Page`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%' AND `Page`.`name` LIKE 'Page 1%') ORDER BY `Author`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    $this->runAnonymous(false);
  }

  public function testParentChildSameType()
  {
    $this->runAnonymous(true);

    $queryStr = "`Page`.`creator` LIKE '%ingo%' AND `ChildPage`.`name` LIKE 'Page 1%'";
    $query = new StringQuery('Page');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, `Page`.`creator`, ".
      "`Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, `Author`.`name` AS `author_name` FROM `Page` ".
      "LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` INNER JOIN `ChildPage` ON `ChildPage`.`fk_page_id` = `Page`.`id` ".
      "WHERE (`Page`.`creator` LIKE '%ingo%' AND `ChildPage`.`name` LIKE 'Page 1%') ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    $this->runAnonymous(false);
  }

  public function testManyToMany()
  {
    $this->runAnonymous(true);

    $queryStr = "`Page`.`name` LIKE '%Page 1%' AND `Document`.`title` = 'Document'";
    $query = new StringQuery('Page');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, ".
      "`Page`.`created`, `Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `NMPageDocument` ON `NMPageDocument`.`fk_page_id` = `Page`.`id` INNER JOIN `Document` ON `Document`.`id` = `NMPageDocument`.`fk_document_id` ".
      "WHERE (`Page`.`name` LIKE '%Page 1%' AND `Document`.`title` = 'Document') ORDER BY `Page`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    $this->runAnonymous(false);
  }

  /**
   * @expectedException IllegalArgumentException
   */
  public function testAmbiguousRelation()
  {
    $this->runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Image`.`file` = 'image.jpg'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();

    $this->runAnonymous(false);
  }

  public function testDifferentRoles()
  {
    $this->runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Page` ON `Page`.`fk_author_id` = `Author`.`id` ".
      "INNER JOIN `NormalImage` ON `NormalImage`.`fk_page_id` = `Page`.`id` ".
      "INNER JOIN `TitleImage` ON `TitleImage`.`fk_titlepage_id` = `Page`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg') ORDER BY `Author`.`sortkey` ASC";
    $this->assertTrue(str_replace("\n", "", $sql) === $expected);

    $this->runAnonymous(false);
  }
}
?>