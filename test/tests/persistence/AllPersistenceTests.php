<?php
require_once("tests/persistence/ObjectIdTest.php");
require_once("tests/persistence/RoleTest.php");

class AllPersistenceTests {

  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('Persistence');
    $suite->addTestSuite('ObjectIdTest');
    $suite->addTestSuite('RoleTest');
    return $suite;
  }
}
?>
