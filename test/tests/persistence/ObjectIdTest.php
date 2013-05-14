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

use test\lib\BaseTestCase;
use wcmf\lib\persistence\ObjectId;

/**
 * NodeUnifiedRDBMapperTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectIdTest extends BaseTestCase {

  public function testSerialize() {
    // simple
    $oid1 = new ObjectId('UserRDB', 10);
    $this->assertEquals('testapp.application.model.wcmf.UserRDB:10', $oid1->__toString(),
            "The oid is 'testapp.application.model.wcmf.UserRDB:10'");

    // multiple primary keys
    $oid2 = new ObjectId('NMUserRole', array(10, 11));
    $this->assertEquals('testapp.application.model.wcmf.NMUserRole:10:11', $oid2->__toString(),
            "The oid is 'testapp.application.model.wcmf.NMUserRole:10:11'");
  }

  public function testValidate() {
    // ok
    $oidStr1 = 'UserRDB:1';
    $this->assertTrue(ObjectId::isValid($oidStr1), "'UserRDB:1' is valid");

    // unknown type
    $oidStr2 = 'UserWrong:1';
    $this->assertFalse(ObjectId::isValid($oidStr2), "'UserWrong:1' is not valid");

    // too much pks
    $oidStr3 = 'UserRDB:1:2';
    $this->assertFalse(ObjectId::isValid($oidStr3), "'UserRDB:1:2' is not valid");
  }

  public function testParse() {
    // simple
    $oid1 = ObjectId::parse('UserRDB:10');
    $id1 = $oid1->getId();
    $this->assertTrue($oid1->getType() === 'testapp.application.model.wcmf.UserRDB' &&
            is_array($id1) && sizeof($id1) === 1 && $id1[0] === 10, "The oid is valid");

    // multiple primary keys
    $oid2 = ObjectId::parse('NMUserRole:10:11');
    $id2 = $oid2->getId();
    $this->assertTrue($oid2->getType() === 'testapp.application.model.wcmf.NMUserRole' &&
            is_array($id2) && sizeof($id2) === 2 && $id2[0] === 10 && $id2[1] === 11, "The oid is valid");
  }

  public function testDummy() {
    // simple
    $oid = new ObjectId('UserRDB');
    $id = $oid->getId();
    $this->assertTrue(ObjectId::isDummyId($id[0]), "The id is a dummy id");
  }
}
?>