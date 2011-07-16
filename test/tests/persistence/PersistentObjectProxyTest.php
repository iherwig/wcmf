<?php
require_once(WCMF_BASE."wcmf/lib/persistence/ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistentObjectProxy.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class PersistentObjectProxyTest extends PHPUnit_Framework_TestCase
{
  private $_page1OidStr = 'Page:123451';
  private $_page2OidStr = 'Page:123452';

  protected function setUp()
  {
    TestUtil::runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page1 = TestUtil::createTestObject(ObjectId::parse($this->_page1OidStr), array('name' => 'Page1'));
    $page2 = TestUtil::createTestObject(ObjectId::parse($this->_page2OidStr), array('name' => 'Page2'));
    $page1->addNode($page2, "ChildPage");
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown()
  {
    TestUtil::runAnonymous(true);
    $transaction = PersistenceFacade::getInstance()->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_page1OidStr));
    TestUtil::deleteTestObject(ObjectId::parse($this->_page2OidStr));
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testLoadSimple()
  {
    TestUtil::runAnonymous(true);
    $proxy = new PersistentObjectProxy(ObjectId::parse($this->_page1OidStr));
    $this->assertEquals("Page1", $proxy->getName());
    $this->assertEquals(123451, $proxy->getSortkeyPage());
    $this->assertTrue($proxy->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");

    $proxy = PersistentObjectProxy::fromObject($proxy->getRealSubject());
    $this->assertEquals("Page1", $proxy->getName());
    $this->assertEquals(123451, $proxy->getSortkeyPage());
    $this->assertTrue($proxy->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");
    TestUtil::runAnonymous(false);
  }

  public function testLoadRelation()
  {
    TestUtil::runAnonymous(true);
    $proxy = new PersistentObjectProxy(ObjectId::parse($this->_page1OidStr));
    $page1 = $proxy->getRealSubject();
    $this->assertEquals("Page1", $proxy->getName());
    $this->assertEquals(123451, $proxy->getSortkeyPage());
    $this->assertTrue($page1 instanceof PersistentObject, "Real subject is PersistentObject instance");

    // implicitly load relation
    $childPages = $page1->getValue("ChildPage");
    $page2 = $childPages[0];
    $this->assertEquals("Page2", $page2->getName());
    $this->assertEquals(123452, $page2->getSortkeyPage());
  }

}
?>