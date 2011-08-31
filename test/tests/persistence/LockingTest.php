<?php
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/concurrency/ConcurrencyManager.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class LockingTest extends PHPUnit_Framework_TestCase
{
  public function testSimple()
  {
    $sid = TestUtil::startSession('admin', 'admin');
    ConcurrencyManager::getInstance()->aquireLock(new ObjectId('UserRDB', 2),
            Lock::TYPE_PESSIMISTIC);
    // TODO check for lock in database

    TestUtil::endSession($sid);

    // TODO check if no lock stays in database
  }
}
?>