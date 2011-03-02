<?php
require_once("tests/format/JSONFormatTest.php");

class AllFormatTests
{
  public static function suite()
  {
    $suite = new PHPUnit_Framework_TestSuite('Format');
    $suite->addTestSuite('JSONFormatTest');
    return $suite;
  }
}
?>
