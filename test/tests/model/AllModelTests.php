<?php
require_once("tests/model/IteratorTest.php");
require_once("tests/model/NodeUtilTest.php");

class AllModelTests
{
  public static function suite()
  {
    $suite = new PHPUnit_Framework_TestSuite('Model');
    $suite->addTestSuite('IteratorTest');
    $suite->addTestSuite('NodeUtilTest');
    return $suite;
  }
}
?>
