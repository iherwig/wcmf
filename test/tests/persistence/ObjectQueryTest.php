<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.Criteria.php");
require_once(WCMF_BASE."wcmf/lib/model/class.ObjectQuery.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class ObjectQueryTest extends WCMFTestCase
{
  public function testSimple()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Author');
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $cond = $query->getQueryCondition();
    $this->assertEquals('', $cond);

    $this->runAnonymous(false);
  }

  public function testOneNode()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Author');
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", "admin"); // implicit LIKE
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' ".
      "AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $cond = $query->getQueryCondition();
    $this->assertEquals("(`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%')", $cond);

    $this->runAnonymous(false);
  }

  public function testAttribs()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Author');
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", Criteria::asValue("IN", array("admin", "ingo"))); // explicit set
    //
    // we need to execute the query first in order to define the attributes
    $query->execute(BUILDDEPTH_SINGLE, null, null, array('name'));
    $sql = $query->getLastQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' AND ".
      "`Author`.`creator` IN ('admin', 'ingo')) ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testOrderby()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Author');
    //
    // we need to execute the query first in order to define the attributes
    $query->execute(BUILDDEPTH_SINGLE, array('name ASC', 'created DESC'), null, array());
    $sql = $query->getLastQueryString();
    $expected = "SELECT `Author`.`id` FROM `Author` ORDER BY `Author`.`name` ASC, `Author`.`created` DESC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testOneNodeRegistered()
  {
    $this->runAnonymous(true);

    $persistenceFacade = PersistenceFacade::getInstance();
    $authorTpl = $persistenceFacade->create('Author', BUILDDEPTH_SINGLE);
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl->setValue("creator", "admin"); // implicit LIKE

    $query = new ObjectQuery('Author');
    $query->registerObjectTemplate($authorTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` WHERE (`Author`.`name` LIKE '%ingo%' ".
      "AND `Author`.`creator` LIKE '%admin%') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testParentChild()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Author');
    $authorTpl = $query->getObjectTemplate('Author');
    $authorTpl->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $pageTpl = $query->getObjectTemplate('Page');
    $pageTpl->setValue("name", Criteria::asValue("LIKE", "Page 1%")); // explicit LIKE
    $authorTpl->addNode($pageTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Page` ON `Page`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%') AND (`Page`.`name` LIKE 'Page 1%') ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testParentChildSameType()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Page');
    $page1Tpl = $query->getObjectTemplate('Page');
    $page1Tpl->setValue("creator", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $page2Tpl = $query->getObjectTemplate('Page');
    $page2Tpl->setValue("name", Criteria::asValue("LIKE", "Page 1%")); // explicit LIKE
    $page1Tpl->addNode($page2Tpl, 'ChildPage');
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, `Page`.`created`, ".
      "`Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` INNER JOIN `Page` AS `Page_1` ON ".
      "`Page_1`.`fk_page_id` = `Page`.`id` WHERE (`Page`.`creator` LIKE '%ingo%') AND (`Page_1`.`name` LIKE 'Page 1%') ".
      "ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testManyToMany()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Page');
    $pageTpl = $query->getObjectTemplate('Page');
    $pageTpl->setValue("name", Criteria::asValue("LIKE", "%Page 1%")); // explicit LIKE
    $documentTpl = $query->getObjectTemplate('Document');
    $documentTpl->setValue("title", Criteria::asValue("=", "Document")); // explicit LIKE
    $pageTpl->addNode($documentTpl);
    $sql = $query->getQueryString();
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, ".
      "`Page`.`created`, `Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `NMPageDocument` ON `NMPageDocument`.`fk_page_id` = `Page`.`id` INNER JOIN `Document` ON `Document`.`id` = `NMPageDocument`.`fk_document_id` ".
      "WHERE (`Page`.`name` LIKE '%Page 1%') AND (`Document`.`title` = 'Document') ORDER BY `Page`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testColumnNotEqualsAttribute()
  {
    $this->runAnonymous(true);

    $oid = new ObjectId('UserRDB', array(2));
    $query = new ObjectQuery('Locktable');
    $tpl = $query->getObjectTemplate('Locktable');
    $tpl->setValue('sessionid', Criteria::asValue("=", "7pkt0i3ojm67s9qb66dih5nd60"));
    $tpl->setValue('objectid', Criteria::asValue("=", $oid));
    $sql = $query->getQueryString();
    $expected = "SELECT `locktable`.`id`, `locktable`.`fk_user_id`, `locktable`.`objectid`, `locktable`.`sessionid`, ".
      "`locktable`.`since` FROM `locktable` WHERE (`locktable`.`objectid` = 'UserRDB:2' AND ".
      "`locktable`.`sessionid` = '7pkt0i3ojm67s9qb66dih5nd60')";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testSortManyToManyRelation()
  {
    $this->runAnonymous(true);

    $query = new ObjectQuery('Page');
    $pageTpl = $query->getObjectTemplate('Page');
    $pageTpl->setValue("name", Criteria::asValue("LIKE", "%Page 1%")); // explicit LIKE
    $documentTpl = $query->getObjectTemplate('Document');
    $documentTpl->setValue("title", Criteria::asValue("=", "Document")); // explicit LIKE
    $pageTpl->addNode($documentTpl);
    $sql = $query->getQueryString(array('sortkey_document DESC'));
    $expected = "SELECT `Page`.`id`, `Page`.`fk_page_id`, `Page`.`fk_author_id`, `Page`.`name`, ".
      "`Page`.`created`, `Page`.`creator`, `Page`.`modified`, `Page`.`last_editor`, `Page`.`sortkey_author`, `Page`.`sortkey_page`, `Page`.`sortkey`, ".
      "`Author`.`name` AS `author_name` FROM `Page` LEFT JOIN `Author` ON `Page`.`fk_author_id`=`Author`.`id` ".
      "INNER JOIN `NMPageDocument` ON `NMPageDocument`.`fk_page_id` = `Page`.`id` INNER JOIN `Document` ON `Document`.`id` = `NMPageDocument`.`fk_document_id` ".
      "WHERE (`Page`.`name` LIKE '%Page 1%') AND (`Document`.`title` = 'Document') ORDER BY `NMPageDocument`.`sortkey_document` DESC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }

  public function testComplex()
  {
    $this->runAnonymous(true);

    /*
    WHERE (Author.name LIKE '%ingo%' AND Author.creator LIKE '%admin%') OR (Author.name LIKE '%herwig%') AND
      (Page.created >= '2004-01-01') AND (Page.created < '2005-01-01') AND ((Page.name LIKE 'Page 1%') OR (Page.creator = 'admin'))
     */

    $query = new ObjectQuery('Author');

    // (Author.name LIKE '%ingo%' AND Author.creator LIKE '%admin%')
    $authorTpl1 = $query->getObjectTemplate('Author');
    $authorTpl1->setValue("name", Criteria::asValue("LIKE", "%ingo%")); // explicit LIKE
    $authorTpl1->setValue("creator", "admin"); // implicit LIKE

    // OR (Author.name LIKE '%herwig%')
    $authorTpl2 = $query->getObjectTemplate('Author', null, Criteria::OPERATOR_OR);
    $authorTpl2->setValue("name", "herwig");

    // (Page.created >= '2004-01-01') AND (Page.created < '2005-01-01')
    $pageTpl1 = $query->getObjectTemplate('Page');
    $pageTpl1->setValue("created", Criteria::asValue(">=", "2004-01-01"));
    $pageTpl2 = $query->getObjectTemplate('Page');
    $pageTpl2->setValue("created", Criteria::asValue("<", "2005-01-01"));

    // AND ((Page.name LIKE 'Page 1%') OR (Page.creator = 'admin'))
    // could have be built using one template, but this demonstrates the usage
    // of the ObjectQuery::makeGroup() method
    $pageTpl3 = $query->getObjectTemplate('Page');
    $pageTpl3->setValue("name", Criteria::asValue("LIKE", "Page 1%"));
    $pageTpl4 = $query->getObjectTemplate('Page', null, Criteria::OPERATOR_OR);
    $pageTpl4->setValue("creator", Criteria::asValue("=", "admin"));
    $query->makeGroup(array(&$pageTpl3, &$pageTpl4), Criteria::OPERATOR_AND);

    $authorTpl1->addNode($pageTpl1, 'Page');
    $authorTpl1->addNode($pageTpl2, 'Page');
    $authorTpl1->addNode($pageTpl3, 'Page');
    $authorTpl1->addNode($pageTpl4, 'Page');
    $sql = $query->getQueryString();
    $expected = "SELECT `Author`.`id`, `Author`.`name`, `Author`.`created`, `Author`.`creator`, `Author`.`modified`, ".
      "`Author`.`last_editor`, `Author`.`sortkey` FROM `Author` INNER JOIN `Page` ON `Page`.`fk_author_id` = `Author`.`id` ".
      "WHERE (`Author`.`name` LIKE '%ingo%' AND `Author`.`creator` LIKE '%admin%') AND (`Page`.`created` >= '2004-01-01') ".
      "AND (`Page`.`created` < '2005-01-01') OR (`Author`.`name` LIKE '%herwig%') AND ".
      "((`Page`.`name` LIKE 'Page 1%') OR (`Page`.`creator` = 'admin')) ORDER BY `Author`.`sortkey` ASC";
    $this->assertEquals($expected, str_replace("\n", "", $sql));

    $this->runAnonymous(false);
  }
}
?>