<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistentObjectProxy.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class PersistentObjectProxyTest extends WCMFTestCase
{
  public function testLoad()
  {
    $this->runAnonymous(true);

    $oid = new ObjectId('UserRDB', 300);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    $this->createTestObject($oid, array("name" => "admin"));
    $transaction->commit();

    $proxy = new PersistentObjectProxy($oid);
    $this->assertEquals("admin", $proxy->getName(), "The user's name is admin");
    $this->assertTrue($proxy->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");

    $proxy = PersistentObjectProxy::fromObject($proxy->getRealSubject());
    $this->assertEquals("admin", $proxy->getName(), "The user's name is admin");
    $this->assertTrue($proxy->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");

    $transaction->begin();
    $this->deleteTestObject($oid);
    $transaction->commit();
    $this->runAnonymous(false);
  }
}
?>