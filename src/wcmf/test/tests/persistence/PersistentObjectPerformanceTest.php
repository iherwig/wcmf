<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\util\TestUtil;

/**
 * PersistentObjectPerformanceTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectPerformanceTest extends DatabaseTestCase {

  private static $_logger = null;

  protected function getDataSet() {
    $chapters = array();
    for ($i=0; $i<1000; $i++) {
      $chapters[] = array('id' => $i, 'sortkey' => $i);
    }

    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'Chapter' => $chapters,
    ));
  }

  protected function setUp() {
    parent::setUp();
    if (self::$_logger == null) {
      self::$_logger = ObjectFactory::getInstance('logManager')->getLogger(__CLASS__);
    }
  }


  /**
   * @group performance
   */
  public function testCreateRandom() {
    TestUtil::runAnonymous(true);
    $alphanum = "abcdefghijkmnpqrstuvwxyz23456789";
    $pf = ObjectFactory::getInstance('persistenceFacade');
    for ($i=0; $i<1000; $i++) {
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
    self::$_logger->info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds");
    self::$_logger->info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes");
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
    self::$_logger->info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds");
    self::$_logger->info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes");
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
    self::$_logger->info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds");
    self::$_logger->info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes");
    TestUtil::runAnonymous(false);
  }
}
?>