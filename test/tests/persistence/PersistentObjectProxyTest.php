<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
namespace test\tests\persistence;

use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObjectProxy;

/**
 * PersistentObjectProxyTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectProxyTest extends \PHPUnit_Framework_TestCase {

  private $_page1OidStr = 'Page:123451';
  private $_page2OidStr = 'Page:123452';

  protected function setUp() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $page1 = TestUtil::createTestObject(ObjectId::parse($this->_page1OidStr), array('name' => 'Page1'));
    $page2 = TestUtil::createTestObject(ObjectId::parse($this->_page2OidStr), array('name' => 'Page2'));
    $page1->addNode($page2, "ChildPage");
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  protected function tearDown() {
    TestUtil::runAnonymous(true);
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
    $transaction->begin();
    TestUtil::deleteTestObject(ObjectId::parse($this->_page1OidStr));
    TestUtil::deleteTestObject(ObjectId::parse($this->_page2OidStr));
    $transaction->commit();
    TestUtil::runAnonymous(false);
  }

  public function testLoadSimple() {
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

  public function testLoadRelation() {
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