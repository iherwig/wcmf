<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * PersistentObjectPerformanceTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectPerformanceTest extends DatabaseTestCase {

  protected function getDataSet() {
    $chapters = array();
    for ($i=0; $i<1000; $i++) {
      $chapters[] = array('id' => $i, 'sortkey' => $i);
    }

    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''),
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
      ),
      'Chapter' => $chapters,
    ));
  }

  /**
   * @group performance
   */
  public function testCreateRandom() {
    TestUtil::startSession('admin', 'admin');
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
    TestUtil::endSession();
  }

  /**
   * @group performance
   */
  public function testLoadMany() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $start = time();
    $chapters = $persistenceFacade->loadObjects('Chapter', BuildDepth::SINGLE);
    $logger = $this->getLogger(__CLASS__);
    $logger->info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds");
    $logger->info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes");
    TestUtil::endSession();
  }

  /**
   * @group performance
   */
  public function testLoadManyObjectQuery() {
    TestUtil::startSession('admin', 'admin');
    $start = time();
    $query = new ObjectQuery('Chapter');
    $chapters = $query->execute(BuildDepth::SINGLE);
    $logger = $this->getLogger(__CLASS__);
    $logger->info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds");
    $logger->info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes");
    TestUtil::endSession();
  }

   /**
   * @group performance
   */
  public function testLoadManyObjectQueryNoCache() {
    TestUtil::startSession('admin', 'admin');
    $start = time();
    $query = new ObjectQuery('Chapter', SelectStatement::NO_CACHE);
    $chapters = $query->execute(BuildDepth::SINGLE);
    $logger = $this->getLogger(__CLASS__);
    $logger->info("Loaded ".sizeof($chapters)." chapters in ".(time()-$start)." seconds");
    $logger->info("Size of chapter: ".TestUtil::getSizeof($chapters[0])." bytes");
    TestUtil::endSession();
  }
}
?>