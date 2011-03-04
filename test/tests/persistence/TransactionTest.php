<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class TransactionTest extends WCMFTestCase
{
  public function testSimple()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $persistenceFacade->startTransaction();
    $persistenceFacade->commitTransaction();

    $this->runAnonymous(false);
  }
}
?>