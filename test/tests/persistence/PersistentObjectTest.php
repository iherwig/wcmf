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

use new_roles\application\model\Page;

use test\lib\TestUtil;
use wcmf\lib\core\Log;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * PersistentObjectTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectTest extends \PHPUnit_Framework_TestCase {

  protected function setUp() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    for ($i=100000; $i<100000+11; $i++) {
      TestUtil::createTestObject(ObjectId::parse('Page:'.$i), array());
    }
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown() {
    TestUtil::runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    for ($i=100000; $i<100000+11; $i++) {
      TestUtil::deleteTestObject(ObjectId::parse('Page:'.$i));
    }
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testInitializeSortkeys() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $page = $persistenceFacade->load(ObjectId::parse('Page:100000'));
    $this->assertEquals(100000, $page->getSortkey());
    $this->assertEquals(100000, $page->getSortkeyPage());
    $this->assertEquals(100000, $page->getSortkeyAuthor());

    TestUtil::runAnonymous(false);
  }

  public function testCopyValues() {
    TestUtil::runAnonymous(true);
    $page1 = new Page(new ObjectId('Page', 123));
    $page1->setName('Page 1');
    $page1->setCreated(null);

    // copy values without pks
    $page21 = new Page(new ObjectId('Page', 234));
    $page21->setName('Page 2');
    $page1->setCreated('2011-05-31');
    $page1->copyValues($page21, false);
    $this->assertEquals('Page:234', $page21->getOID()->__toString());
    $this->assertEquals('Page 1', $page21->getName());
    $this->assertEquals('2011-05-31', $page21->getCreated());

    // copy values without pks
    $page22 = new Page(new ObjectId('Page', 234));
    $page22->setName('Page 2');
    $page1->setCreated('2011-05-31');
    $page1->copyValues($page22, true);
    $this->assertEquals('Page:123', $page22->getOID()->__toString());
    $this->assertEquals('Page 1', $page22->getName());
    $this->assertEquals('2011-05-31', $page22->getCreated());

    TestUtil::runAnonymous(false);
  }

  public function testMergeValues() {
    TestUtil::runAnonymous(true);
    $page1 = new Page(new ObjectId('Page', 123));
    $page1->setName('Page 1');
    $page1->setCreated('2011-05-31');
    $page1->setCreator('admin');

    $page2 = new Page(new ObjectId('Page', 234));
    $page2->setName('Page 2');
    $page1->setCreated(null);
    $page2->mergeValues($page1);
    $this->assertEquals('Page:234', $page2->getOID()->__toString());
    $this->assertEquals('Page 2', $page2->getName());
    $this->assertEquals(null, $page2->getCreated());
    $this->assertEquals('admin', $page2->getCreator());

    TestUtil::runAnonymous(false);
  }

  public function testClearValues() {
    TestUtil::runAnonymous(true);
    $page1 = new Page(new ObjectId('Page', 123));
    $page1->setName('Page 1');
    $page1->setCreated('2011-05-31');

    $page1->clearValues();
    $this->assertEquals('Page:123', $page1->getOID()->__toString());
    $this->assertEquals(null, $page1->getName());
    $this->assertEquals(null, $page1->getCreated());

    TestUtil::runAnonymous(false);
  }

  public function testLoadPartially() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $pagePartially = $persistenceFacade->load(ObjectId::parse('Page:100000'), BUILDDEPTH_SINGLE, array('Page' => array()));
    $this->assertFalse($pagePartially->hasValue('sortkey_page'));
    $this->assertEquals(null, $pagePartially->getSortkeyPage());

    $pageComplete = $persistenceFacade->load(ObjectId::parse('Page:100000'), BUILDDEPTH_SINGLE);
    $this->assertTrue($pagePartially->hasValue('sortkey_page'));
    $this->assertEquals(100000, $pageComplete->getSortkeyPage());

    TestUtil::runAnonymous(false);
  }

  public function testLoadPaging() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    // lower bound 1
    $pagingInfo = new PagingInfo(0);
    $pages = $persistenceFacade->loadObjects('Page', BUILDDEPTH_SINGLE, null, null, $pagingInfo);
    $this->assertEquals(0, sizeof($pages));

    // lower bound 2
    $pagingInfo = new PagingInfo(1);
    $pages = $persistenceFacade->loadObjects('Page', BUILDDEPTH_SINGLE, null, null, $pagingInfo);
    $this->assertEquals(1, sizeof($pages));

    // simple
    $pagingInfo = new PagingInfo(10);
    $pages = $persistenceFacade->loadObjects('Page', BUILDDEPTH_SINGLE, null, null, $pagingInfo);
    $this->assertEquals(10, sizeof($pages));

    // out of bounds 1
    $pagingInfo = new PagingInfo(-1);
    $pages = $persistenceFacade->loadObjects('Page', BUILDDEPTH_SINGLE, null, null, $pagingInfo);
    $this->assertEquals(0, sizeof($pages));

    // out of bounds 2
    $pagingInfo = new PagingInfo(100000000);
    $numPages = sizeof($persistenceFacade->getOIDs('Page'));
    $pages = $persistenceFacade->loadObjects('Page', BUILDDEPTH_SINGLE, null, null, $pagingInfo);
    $this->assertEquals($numPages, sizeof($pages));

    TestUtil::runAnonymous(false);
  }

  /**
   * @group performance
   */
  public function testCreateRandom() {
    TestUtil::runAnonymous(true);
    $alphanum = "abcdefghijkmnpqrstuvwxyz23456789";
    $pf = PersistenceFacade::getInstance();
    for ($i=0; $i<100; $i++) {
      $doc = $pf->create('Page', BUILDDEPTH_SINGLE);
      $inc = 1;
      while ($inc < 15){
        $alphanum = $alphanum.'abcdefghijkmnpqrstuvwxyz23456789';
        $inc++;
      }
      $title = substr(str_shuffle($alphanum), 0, 15);
      $doc->setName(ucfirst($title));
    }
    TestUtil::runAnonymous(false);
  }

  /**
   * @group performance
   */
  public function testLoadManyWithAllAttributes() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $start = time();
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE);
    Log::info("Loaded ".sizeof($documents)." documents with all attributes in ".(time()-$start)." seconds", __CLASS__);
    TestUtil::runAnonymous(false);
  }

  /**
   * @group performance
   */
  public function testLoadManyWithOneAttribute() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $start = time();
    $documents = $persistenceFacade->loadObjects('Document', BUILDDEPTH_SINGLE,
            null, null, null, array('Document' => array('id')));
    Log::info("Loaded ".sizeof($documents)." documents with one attribute in ".(time()-$start)." seconds", __CLASS__);
    TestUtil::runAnonymous(false);
  }
}
?>