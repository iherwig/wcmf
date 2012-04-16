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
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * NodeTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeTest extends \PHPUnit_Framework_TestCase {

  public function testBuildDepth() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();

    $page1 = $persistenceFacade->create('Page', BuildDepth::SINGLE);
    $this->assertEquals(0, sizeof($page1->getValue('ChildPage')));
    $this->assertEquals(0, sizeof($page1->getValue('ParentPage')));
    $this->assertEquals(0, sizeof($page1->getValue('Author')));
    $this->assertEquals(0, sizeof($page1->getValue('NormalImage')));
    $this->assertEquals(0, sizeof($page1->getValue('TitleImage')));
    $this->assertEquals(0, sizeof($page1->getValue('Document')));

    $page2 = $persistenceFacade->create('Page', BuildDepth::REQUIRED);
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

    $author1 = $persistenceFacade->create('Author', BuildDepth::SINGLE);
    $this->assertEquals(0, sizeof($author1->getValue('Page')));

    $author2 = $persistenceFacade->create('Author', BuildDepth::REQUIRED);
    $this->assertNotEquals(0, sizeof($author2->getValue('Page')));

    // BUILDDEPTH_INFINTE is not allowed for create
    try {
      $persistenceFacade->create('Page', BuildDepth::INFINITE);
      $this->fail('An expected exception has not been raised.');
    }
    catch(Exception $ex) {}

    $transaction->rollback();
    TestUtil::runAnonymous(false);
  }
}
?>