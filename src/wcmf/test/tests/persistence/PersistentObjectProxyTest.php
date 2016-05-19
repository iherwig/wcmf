<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PersistentObjectProxy;
use wcmf\lib\util\TestUtil;

/**
 * PersistentObjectProxyTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObjectProxyTest extends DatabaseTestCase {

  private $chapter1OidStr = 'Chapter:123451';

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'config' => ''),
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
      ),
      'Chapter' => array(
        array('id' => 123451, 'fk_chapter_id' => null, 'name' => 'Chapter1', 'sortkey' => 123451),
        array('id' => 123452, 'fk_chapter_id' => 123451, 'name' => 'Chapter2', 'sortkey' => 123452),
      ),
    ));
  }

  public function testLoadSimple() {
    TestUtil::startSession('admin', 'admin');
    $proxy1 = new PersistentObjectProxy(ObjectId::parse($this->chapter1OidStr));
    $this->assertEquals("Chapter1", $proxy1->getValue('name'));
    $this->assertEquals(123451, $proxy1->getValue('sortkey'));
    $this->assertTrue($proxy1->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");

    $proxy2 = PersistentObjectProxy::fromObject($proxy1->getRealSubject());
    $this->assertEquals("Chapter1", $proxy2->getValue('name'));
    $this->assertEquals(123451, $proxy2->getValue('sortkey'));
    $this->assertTrue($proxy2->getRealSubject() instanceof PersistentObject, "Real subject is PersistentObject instance");
    TestUtil::endSession();
  }

  public function testLoadRelation() {
    TestUtil::startSession('admin', 'admin');
    $proxy = new PersistentObjectProxy(ObjectId::parse($this->chapter1OidStr));
    $chapter1 = $proxy->getRealSubject();
    $this->assertEquals("Chapter1", $proxy->getValue('name'));
    $this->assertEquals(123451, $proxy->getValue('sortkey'));
    $this->assertTrue($chapter1 instanceof PersistentObject, "Real subject is PersistentObject instance");

    // implicitly load relation
    $subChapters = $chapter1->getValue("SubChapter");
    $chapter2 = $subChapters[0];
    $this->assertEquals("Chapter2", $chapter2->getValue('name'));
    $this->assertEquals(123452, $chapter2->getValue('sortkey'));
  }
}
?>