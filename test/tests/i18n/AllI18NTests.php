<?php
require_once("tests/i18n/LocalizationTest.php");

class AllI18NTests
{
  public static function suite()
  {
    $suite = new PHPUnit_Framework_TestSuite('I18N');
    $suite->addTestSuite('LocalizationTest');
    return $suite;
  }
}
?>
