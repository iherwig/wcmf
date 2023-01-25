<?php
namespace Tests\Support\Helper;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\TestUtil;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
  public function _beforeSuite($settings = [])
  {
    TestUtil::initFramework(WCMF_BASE.'app/config/');
  }

  public function _before(\Codeception\TestInterface $test)
  {
    TestUtil::initFramework(WCMF_BASE.'app/config/');
  }

  /**
   * Get the logger for the given category
   * @param $category
   * @return Logger
   */
  public function getLogger($category) {
    return LogManager::getLogger($category);
  }

  /**
   * Replace backticks in the given sql string by the actual quote char
   * used in the connection
   * @param $sql
   * @param $type The type defining the connection parameters
   * @return String
   */
  public function fixQueryQuotes($sql, $type) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($type);
    return str_replace('`', $mapper->getQuoteIdentifierSymbol(), $sql);
  }
}
