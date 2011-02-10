<?php
require_once("tests/format/AllFormatTests.php");
require_once("tests/persistence/AllPersistenceTests.php");

class AllTests {

  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('All');
    $suite->addTestSuite(AllFormatTests::suite());
    $suite->addTestSuite(AllPersistenceTests::suite());
    return $suite;
  }
}
?>
