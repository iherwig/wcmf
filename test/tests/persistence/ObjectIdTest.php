<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."test/lib/TestUtil.php");

class ObjectIdTest extends PHPUnit_Framework_TestCase
{
  public function testSerialize()
  {
    // simple
    $oid = new ObjectId('UserRDB', 10);
    $this->assertEquals('UserRDB:10', $oid->__toString(), "The oid is 'UserRDB:10'");

    // multiple primary keys
    $oid = new ObjectId('NMUserRole', array(10, 11));
    $this->assertEquals('NMUserRole:10:11', $oid->__toString(), "The oid is 'NMUserRole:10:11'");
  }

  public function testValidate()
  {
    // ok
    $oidStr = 'UserRDB:1';
    $this->assertTrue(ObjectId::isValid($oidStr), "'UserRDB:1' is valid");

    // unknown type
    $oidStr = 'UserWrong:1';
    $this->assertFalse(ObjectId::isValid($oidStr), "'UserWrong:1' is not valid");

    // too much pks
    $oidStr = 'UserRDB:1:2';
    $this->assertFalse(ObjectId::isValid($oidStr), "'UserRDB:1:2' is not valid");
  }

  public function testParse()
  {
    // simple
    $oid = ObjectId::parse('UserRDB:10');
    $id = $oid->getId();
    $this->assertTrue($oid->getType() === 'UserRDB' && is_array($id) && sizeof($id) === 1 && $id[0] === 10, "The oid is valid");

    // multiple primary keys
    $oid = ObjectId::parse('NMUserRole:10:11');
    $id = $oid->getId();
    $this->assertTrue($oid->getType() === 'NMUserRole' && is_array($id) && sizeof($id) === 2 && $id[0] === 10 && $id[1] === 11, "The oid is valid");
  }

  public function testDummy()
  {
    // simple
    $oid = new ObjectId('UserRDB');
    $id = $oid->getId();
    $this->assertTrue(ObjectId::isDummyId($id[0]), "The id is a dummy id");
  }
}
?>