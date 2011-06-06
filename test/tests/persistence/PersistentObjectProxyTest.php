<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistentObjectProxy.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class PersistentObjectProxyTest extends PHPUnit_Framework_TestCase
{
  public function testLoad()
  {
    TestUtil::runAnonymous(true);

    $oid = new ObjectId('UserRDB', 300);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    TestUtil::createTestObject($oid, array("name" => "admin"));
    $transaction->commit();

    $proxy = new PersistentObjectProxy($oid);
    $this->assertEquals("admin", $proxy->getName(), "The user's name is admin");
    $this->assertTrue($proxy->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");

    $proxy = PersistentObjectProxy::fromObject($proxy->getRealSubject());
    $this->assertEquals("admin", $proxy->getName(), "The user's name is admin");
    $this->assertTrue($proxy->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");

    $transaction->begin();
    TestUtil::deleteTestObject($oid);
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }
}
?>