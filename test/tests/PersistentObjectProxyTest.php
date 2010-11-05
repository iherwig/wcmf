<?php
require_once(BASE."wcmf/3rdparty/adodb/adodb.inc.php");
require_once(BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(BASE."wcmf/lib/persistence/class.PersistentObjectProxy.php");

class PersistentObjectProxyTest extends WCMFTestCase
{
  public function testLoad()
  {
    $this->runAnonymous(true);

    $proxy = new PersistentObjectProxy(new ObjectId('UserRDB', 3));

    $this->assertTrue($proxy->getLogin() == "admin", "The user's name is admin");
    $this->assertTrue($proxy->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");

    $this->runAnonymous(false);
  }
}
?>