<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\lib;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\TestUtil;

/**
 * SeleniumTestCase is the base class for test cases that run with Selenium.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class SeleniumTestCase extends \PHPUnit_Extensions_Selenium2TestCase {

  /**
   * @see http://getbootstrap.com/css/#grid-media-queries
   */
  private $displayWidths = array(
      /* Extra small devices (phones, less than 768px) */
      'xsmall'  => 480,
      /* Small devices (tablets, 768px and up) */
      'small' => 768,
      /* Medium devices (desktops, 992px and up) */
      'medium' => 992,
      /* Large devices (large desktops, 1200px and up) */
      'large' => 1200,
  );
  private $width = 1024;

  private $databaseTester;

  protected static function getAppUrl() {
    return "http://".SERVER_HOST.":".SERVER_PORT;
  }

  protected function setUp() {
    // framework setup
    TestUtil::initFramework(WCMF_BASE.'app/config/');

    // database setup
    $params = TestUtil::createDatabase();
    $conn = new \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($params['connection'], $params['dbName']);

    $this->databaseTester = new \PHPUnit_Extensions_Database_DefaultTester($conn);
    $this->databaseTester->setSetUpOperation(\PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT());
    $this->databaseTester->setDataSet($this->getDataSet());
    $this->databaseTester->onSetUp();

    // selenium setup
    $this->setBrowser('firefox');
    $this->setBrowserUrl(self::getAppUrl());
    parent::setUp();
  }

  public function tearDown() {
    if ($this->databaseTester) {
      $this->databaseTester->setTearDownOperation(\PHPUnit_Extensions_Database_Operation_Factory::NONE());
      $this->databaseTester->setDataSet($this->getDataSet());
      $this->databaseTester->onTearDown();
      $this->databaseTester = NULL;
    }
    parent::tearDown();
  }

  public function setUpPage() {
    parent::setUpPage();

    // get window object
    $window = $this->currentWindow();

    // set window size
    $window->size(array(
      'width' => $this->width,
      'height' => 768)
    );
  }

  protected function setDisplay($size) {
    if (isset($this->displayWidths[$size])) {
      $this->width = $this->displayWidths[$size];
      $this->setUpPage();
    }
  }

  /**
   * Get the test data
   * @return \PHPUnit_Extensions_Database_DataSet_YamlDataSet
   */
  protected function getDataSet() {
    return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet("fixtures/default.yml");
  }

  /**
   * Wait for a DOM element matching the given xpath
   * @param $xpath The xpath
   * @param $wait maximum (in seconds)
   * @retrn element|false false on time-out
   */
  protected function waitForXpath($xpath, $wait=30) {
    for ($i=0; $i <= $wait; $i++) {
      try {
        $x = $this->byXPath($xpath);
        return $x;
      }
      catch (Exception $e) {
        sleep(1);
      }
    }
    return false;
  }

  /**
   * Log into the application
   * @param $user The username
   * @param $password The password
   */
  protected function login($user, $password) {
    $this->url(self::getAppUrl());
    $this->timeouts()->implicitWait(5000);
    $this->byName('user')->value($user);
    $this->byName('password')->value($password);
    $btn = $this->byXPath("//span[contains(text(),'Sign in')]");
    $btn->click();
  }
}
?>