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
namespace wcmf\test\tests\persistence;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;
use wcmf\test\lib\TestUtil;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;

/**
 * PersistentObjectPerformanceTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectPerformanceTest extends DatabaseTestCase {

  protected function getDataSet() {
    $chapters = array();
    for ($i=0; $i<10000; $i++) {
      $chapters[] = array('id' => $i, 'sortkey' => $i);
    }

    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'Chapter' => $chapters,
    ));
  }

  /**
   * @group performance
   */
  public function testCreateRandom() {
    TestUtil::runAnonymous(true);
    $alphanum = "abcdefghijkmnpqrstuvwxyz23456789";
    $pf = ObjectFactory::getInstance('persistenceFacade');
    for ($i=0; $i<100; $i++) {
      $chapter = $pf->create('Chapter', BuildDepth::SINGLE);
      $inc = 1;
      while ($inc < 15){
        $alphanum = $alphanum.'abcdefghijkmnpqrstuvwxyz23456789';
        $inc++;
      }
      $title = substr(str_shuffle($alphanum), 0, 15);
      $chapter->setValue('name', ucfirst($title));
    }
    TestUtil::runAnonymous(false);
  }

  /**
   * @group performance
   */
  public function testLoadMany() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $start = time();
    $chapters = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE);
    Log::info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds", __CLASS__);
    Log::info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes", __CLASS__);
    TestUtil::runAnonymous(false);
  }

  /**
   * @group performance
   */
  public function testLoadManyObjectQuery() {
    TestUtil::runAnonymous(true);
    $start = time();
    $query = new ObjectQuery('Chapter');
    $chapters = $query->execute(BuildDepth::SINGLE);
    Log::info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds", __CLASS__);
    Log::info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes", __CLASS__);
    TestUtil::runAnonymous(false);
  }

   /**
   * @group performance
   */
  public function testLoadManyObjectQueryNoCache() {
    TestUtil::runAnonymous(true);
    $start = time();
    $query = new ObjectQuery('Chapter', SelectStatement::NO_CACHE);
    $chapters = $query->execute(BuildDepth::SINGLE);
    Log::info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds", __CLASS__);
    Log::info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes", __CLASS__);
    TestUtil::runAnonymous(false);
  }
}
?>