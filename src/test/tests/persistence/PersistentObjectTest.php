<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace test\tests\persistence;

use app\src\model\Chapter;

use test\lib\ArrayDataSet;
use test\lib\DatabaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;

/**
 * PersistentObjectTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectTest extends DatabaseTestCase {

  private $_numChapters = 100;

  protected function getDataSet() {
    $chapters = array();
    for ($i=0; $i<$this->_numChapters; $i++) {
      $chapters[] = array('id' => $i, 'sortkey' => $i);
    }

    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'Chapter' => $chapters,
    ));
  }

  public function testCopyValues() {
    TestUtil::runAnonymous(true);
    $chapter1 = new Chapter(new ObjectId('Chapter', 12));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter1->setValue('created', null);

    // copy values without pks
    $chapter21 = new Chapter(new ObjectId('Chapter', 23));
    $chapter21->setValue('name', 'Chapter 2');
    $chapter1->setValue('created', '2011-05-31');
    $chapter1->copyValues($chapter21, false);
    $this->assertEquals('app.src.model.Chapter:23', $chapter21->getOID()->__toString());
    $this->assertEquals('Chapter 1', $chapter21->getValue('name'));
    $this->assertEquals('2011-05-31', $chapter21->getValue('created'));

    // copy values without pks
    $chapter22 = new Chapter(new ObjectId('Chapter', 23));
    $chapter22->setValue('name', 'Chapter 2');
    $chapter1->setValue('created', '2011-05-31');
    $chapter1->copyValues($chapter22, true);
    $this->assertEquals('app.src.model.Chapter:12', $chapter22->getOID()->__toString());
    $this->assertEquals('Chapter 1', $chapter22->getValue('name'));
    $this->assertEquals('2011-05-31', $chapter22->getValue('created'));

    TestUtil::runAnonymous(false);
  }

  public function testMergeValues() {
    TestUtil::runAnonymous(true);
    $chapter1 = new Chapter(new ObjectId('Chapter', 12));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter1->setValue('created', '2011-05-31');
    $chapter1->setValue('creator', 'admin');

    $chapter2 = new Chapter(new ObjectId('Chapter', 23));
    $chapter2->setValue('name', 'Chapter 2');
    $chapter1->setValue('created', null);
    $chapter2->mergeValues($chapter1);
    $this->assertEquals('app.src.model.Chapter:23', $chapter2->getOID()->__toString());
    $this->assertEquals('Chapter 2', $chapter2->getValue('name'));
    $this->assertEquals(null, $chapter2->getValue('created'));
    $this->assertEquals('admin', $chapter2->getValue('creator'));

    TestUtil::runAnonymous(false);
  }

  public function testClearValues() {
    TestUtil::runAnonymous(true);
    $chapter1 = new Chapter(new ObjectId('Chapter', 12));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter1->setValue('created', '2011-05-31');

    $chapter1->clearValues();
    $this->assertEquals('app.src.model.Chapter:12', $chapter1->getOID()->__toString());
    $this->assertEquals(null, $chapter1->getValue('name'));
    $this->assertEquals(null, $chapter1->getValue('created'));

    TestUtil::runAnonymous(false);
  }

  public function testLoadPartially() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $chapterPartially = $persistenceFacade->load(ObjectId::parse('Chapter:99'), BuildDepth::SINGLE, array('Chapter' => array()));
    $this->assertFalse($chapterPartially->hasValue('sortkey'));
    $this->assertEquals(null, $chapterPartially->getValue('sortkey'));

    $chapterComplete = $persistenceFacade->load(ObjectId::parse('Chapter:99'), BuildDepth::SINGLE);
    $this->assertTrue($chapterPartially->hasValue('sortkey'));
    $this->assertEquals(99, $chapterComplete->getValue('sortkey'));

    TestUtil::runAnonymous(false);
  }

  public function testLoadPaging() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // lower bound 1
    $pagingInfo1 = new PagingInfo(0);
    $chapters1 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo1);
    $this->assertEquals(0, sizeof($chapters1));

    // lower bound 2
    $pagingInfo2 = new PagingInfo(1);
    $chapters2 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo2);
    $this->assertEquals(1, sizeof($chapters2));

    // simple
    $pagingInfo3 = new PagingInfo(10);
    $chapters3 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo3);
    $this->assertEquals(10, sizeof($chapters3));

    // out of bounds 1
    $pagingInfo4 = new PagingInfo(-1);
    $chapters4 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo4);
    $this->assertEquals(0, sizeof($chapters4));

    // out of bounds 2
    $pagingInfo5 = new PagingInfo(100000000);
    $chapters5 = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE, null, null, $pagingInfo5);
    $this->assertEquals($this->_numChapters, sizeof($chapters5));

    TestUtil::runAnonymous(false);
  }
}
?>