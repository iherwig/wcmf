<?php
require_once("tests/format/AllFormatTests.php");
require_once("tests/i18n/AllI18NTests.php");
require_once("tests/model/AllModelTests.php");
require_once("tests/persistence/AllPersistenceTests.php");

class AllTests
{
  public static function suite()
  {
    $suite = new PHPUnit_Framework_TestSuite('All');
    $suite->addTestSuite(AllFormatTests::suite());
    $suite->addTestSuite(AllI18NTests::suite());
    $suite->addTestSuite(AllModelTests::suite());
    $suite->addTestSuite(AllPersistenceTests::suite());
    return $suite;
  }
}
?>
