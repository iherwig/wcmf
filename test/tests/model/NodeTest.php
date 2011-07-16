<?php
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class NodeTest extends PHPUnit_Framework_TestCase
{
  public function testBuildDepth()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();

    $page1 = $persistenceFacade->create('Page', BUILDDEPTH_SINGLE);
    $this->assertEquals(0, sizeof($page1->getValue('ChildPage')));
    $this->assertEquals(0, sizeof($page1->getValue('ParentPage')));
    $this->assertEquals(0, sizeof($page1->getValue('Author')));
    $this->assertEquals(0, sizeof($page1->getValue('NormalImage')));
    $this->assertEquals(0, sizeof($page1->getValue('TitleImage')));
    $this->assertEquals(0, sizeof($page1->getValue('Document')));

    $page2 = $persistenceFacade->create('Page', BUILDDEPTH_REQUIRED);
    $this->assertEquals(0, sizeof($page2->getValue('ChildPage')));
    $this->assertEquals(0, sizeof($page2->getValue('ParentPage')));
    $this->assertEquals(0, sizeof($page2->getValue('Author')));
    $this->assertEquals(0, sizeof($page2->getValue('NormalImage')));
    $this->assertEquals(0, sizeof($page2->getValue('TitleImage')));
    $this->assertEquals(0, sizeof($page2->getValue('Document')));

    $page3 = $persistenceFacade->create('Page', 2);
    $this->assertEquals(1, sizeof($page3->getValue('ChildPage')));
    $this->assertEquals(0, sizeof($page3->getValue('ParentPage')));
    $this->assertEquals(0, sizeof($page3->getValue('Author')));
    $this->assertEquals(1, sizeof($page3->getValue('NormalImage')));
    $this->assertEquals(1, sizeof($page3->getValue('TitleImage')));
    $this->assertEquals(1, sizeof($page3->getValue('Document')));

    $childPage1 = $page3->getFirstChild('ChildPage');
    $this->assertEquals(1, sizeof($childPage1->getValue('ChildPage')));
    $this->assertEquals(1, sizeof($childPage1->getValue('ParentPage')));
    $childPage2 = $childPage1->getFirstChild('ChildPage');
    $this->assertEquals(0, sizeof($childPage2->getValue('ChildPage')));
    $this->assertEquals(1, sizeof($childPage2->getValue('ParentPage')));

    $normalImage = $page3->getFirstChild('NormalImage');
    $this->assertEquals(1, sizeof($normalImage->getValue('NormalPage')));

    $titleImage = $page3->getFirstChild('TitleImage');
    $this->assertEquals(1, sizeof($titleImage->getValue('TitlePage')));

    $document = $page3->getFirstChild('Document');
    $this->assertEquals(1, sizeof($document->getValue('Page')));
    $documentPage = $document->getFirstChild('Page');
    $this->assertEquals($documentPage, $page3);

    $author1 = $persistenceFacade->create('Author', BUILDDEPTH_SINGLE);
    $this->assertEquals(0, sizeof($author1->getValue('Page')));

    $author2 = $persistenceFacade->create('Author', BUILDDEPTH_REQUIRED);
    $this->assertNotEquals(0, sizeof($author2->getValue('Page')));

    // BUILDDEPTH_INFINTE is not allowed for create
    try {
      $persistenceFacade->create('Page', BUILDDEPTH_INFINITE);
      $this->fail('An expected exception has not been raised.');
    }
    catch(Exception $ex) {}

    $transaction->rollback();
    TestUtil::runAnonymous(false);
  }
}
?>