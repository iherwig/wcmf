<?php
require_once(WCMF_BASE."wcmf/lib/model/StringQuery.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class StringQueryTest extends PHPUnit_Framework_TestCase
{
  public function testSimple()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, `Author`.`last_editor`, ".
      "`Author`.`sortkey` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testNoCondition()
  {
    TestUtil::runAnonymous(true);

    $query = new StringQuery('Author');
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, `Author`.`last_editor`, ".
      "`Author`.`sortkey` FROM `Author` ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testParentChild()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Page`.`name` LIKE 'Page 1%'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Page` ON `Page`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%' AND `Page`.`name` LIKE 'Page 1%') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testParentChildSameType()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`Page`.`creator` LIKE '%ingo%' AND `ChildPage`.`name` LIKE 'Page 1%'";
    $query = new StringQuery('Page');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` INNER JOIN `Page` AS `ChildPage` ON ".
      "`ChildPage`.`fk_page_id` = `Page`.`id` WHERE (`Page`.`creator` LIKE '%ingo%' AND `ChildPage`.`name` ".
      "LIKE 'Page 1%') ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testChildParent()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`NormalPage`.`id` = 10";
    $query = new StringQuery('Image');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Image`.`id`, `Image`.`fk_page_id`, `Image`.`fk_titlepage_id`, `Image`.`file` AS `filename`, `Image`.`created`, ".
      "`Image`.`creator`, `Image`.`modified`, `Image`.`last_editor` FROM `Image` INNER JOIN `Page` AS `NormalPage` ON ".
      "`Image`.`fk_page_id` = `NormalPage`.`id` WHERE (`NormalPage`.`id` = 10)";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testChildParentSameType()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`ParentPage`.`id` = 10";
    $query = new StringQuery('Page');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` INNER JOIN `Page` AS `ParentPage` ON ".
      "`Page`.`fk_page_id` = `ParentPage`.`id` WHERE (`ParentPage`.`id` = 10) ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testManyToMany()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`Page`.`name` LIKE '%Page 1%' AND `Document`.`title` = 'Document'";
    $query = new StringQuery('Page');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, ".
      "`Page`.`created`, `Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `NMPageDocument` ON `NMPageDocument`.`fk_page_id` = `Page`.`id` INNER JOIN `Document` ON `Document`.`id` = `NMPageDocument`.`fk_document_id` ".
      "WHERE (`Page`.`name` LIKE '%Page 1%' AND `Document`.`title` = 'Document') ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  /**
   * @expectedException IllegalArgumentException
   */
  public function testAmbiguousRelation()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `Image`.`file` = 'image.jpg'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Page` ON `Page`.`fk_author_id` = `Author`.`id` ".
      "INNER JOIN `Image` AS `NormalImage` ON `NormalImage`.`fk_page_id` = `Page`.`id` INNER JOIN `Image` AS `TitleImage` ON ".
      "`TitleImage`.`fk_titlepage_id` = `Page`.`id` WHERE (`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }

  public function testDifferentRoles()
  {
    TestUtil::runAnonymous(true);

    $queryStr = "`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`filename` = 'image.jpg' ".
      "AND `TitleImage`.`filename` = 'title_image.jpg'";
    $query = new StringQuery('Author');
    $query->setConditionString($queryStr);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Page` ON `Page`.`fk_author_id` = `Author`.`id` ".
      "INNER JOIN `Image` AS `NormalImage` ON `NormalImage`.`fk_page_id` = `Page`.`id` INNER JOIN `Image` AS `TitleImage` ON ".
      "`TitleImage`.`fk_titlepage_id` = `Page`.`id` WHERE (`Author`.`name` LIKE '%ingo%' AND `NormalImage`.`file` = 'image.jpg' ".
      "AND `TitleImage`.`file` = 'title_image.jpg') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    TestUtil::runAnonymous(false);
  }
}
?>