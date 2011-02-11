<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class RoleTest extends WCMFTestCase
{
  public function testOneToOneNoRoles()
  {
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $ptpl = $persistenceFacade->create('Page', BUILDDEPTH_SINGLE);
    $ctpl = $persistenceFacade->create('Author', BUILDDEPTH_SINGLE);
    //Log::info($ptpl->toString(), "Test");
    //Log::info($ctpl->toString(), "Test");

    $ptpl->addNode($ctpl);
    //Log::info($ptpl->toString(), "Test");
    //Log::info($ctpl->toString(), "Test");

    //Log::info($persistenceFacade->load(new ObjectId('Page', array(14)), BUILDDEPTH_SINGLE)->toString(), "Test");
    //Log::info($persistenceFacade->load(new ObjectId('Author', array(31)), BUILDDEPTH_SINGLE)->toString(), "Test");
    $this->runAnonymous(false);
  }
}
?>