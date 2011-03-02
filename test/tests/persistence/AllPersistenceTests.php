<?php
require_once("tests/persistence/ObjectIdTest.php");
require_once("tests/persistence/NodeTest.php");
require_once("tests/persistence/NodeRelationTest.php");
require_once("tests/persistence/NodeUnifiedRDBMapperTest.php");
require_once("tests/persistence/PersistentObjectProxyTest.php");
require_once("tests/persistence/RelationDescriptionTest.php");
require_once("tests/persistence/ManyToManyTest.php");
require_once("tests/persistence/ObjectQueryTest.php");
require_once("tests/persistence/StringQueryTest.php");

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
    $suite->addTestSuite('RelationDescriptionTest');
    //$suite->addTestSuite('ManyToManyTest');
    $suite->addTestSuite('ObjectQueryTest');
    $suite->addTestSuite('StringQueryTest');
    return $suite;
  }
}
?>
