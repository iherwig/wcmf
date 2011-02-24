<?php
require_once("tests/persistence/ObjectIdTest.php");
require_once("tests/persistence/NodeTest.php");
require_once("tests/persistence/NodeRelationTest.php");
require_once("tests/persistence/NodeUnifiedRDBMapperTest.php");
require_once("tests/persistence/PersistentObjectProxyTest.php");
require_once("tests/persistence/ManyToManyTest.php");

class AllPersistenceTests
{
  public static function suite()
  {
    $suite = new PHPUnit_Framework_TestSuite('Persistence');
    $suite->addTestSuite('ObjectIdTest');
    $suite->addTestSuite('NodeTest');
    $suite->addTestSuite('NodeRelationTest');
    $suite->addTestSuite('NodeUnifiedRDBMapperTest');
    $suite->addTestSuite('PersistentObjectProxyTest');
    //$suite->addTestSuite('ManyToManyTest');
    return $suite;
  }
}
?>
