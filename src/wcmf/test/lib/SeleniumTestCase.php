<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\lib;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

if (!class_exists('\Facebook\WebDriver\Remote\RemoteWebDriver')) {
    throw new ConfigurationException(
            'SeleniumTestCase requires Facebook\WebDriver. '.
            'If you are using composer, add facebook/webdriver '.
            'as dependency to your project');
}

/**
 * SeleniumTestCase is the base class for test cases that run with Selenium.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class SeleniumTestCase extends DatabaseTestCase {
  use TestTrait;

  /**
   * @see https://getbootstrap.com/docs/4.1/layout/overview/#responsive-breakpoints
   */
  private static $displayWidths = [
    /* Small devices (landscape phones, 576px and up) */
    'small'  => 576,
    /* Medium devices (tablets, 768px and up) */
    'medium' => 768,
    /* Large devices (desktops, 992px and up) */
    'large' => 992,
    /* Extra large devices (large desktops, 1200px and up) */
    'xlarge' => 1200,
  ];
  private static $height = 768;

  protected $driver = null;

  protected static function getAppUrl() {
    if (!defined('TEST_SERVER')) {
      throw new \RuntimeException("Constant TEST_SERVER not defined, e.g. define(TEST_SERVER, 'localhost:8500')");
    }
    return "http://".TEST_SERVER;
  }

  protected static function getSeleniumUrl() {
    if (!defined('SELENIUM_SERVER')) {
      throw new \RuntimeException("Constant SELENIUM_SERVER not defined, e.g. define(SELENIUM_SERVER, 'localhost:8001')");
    }
    return "http://".SELENIUM_SERVER;
  }

  protected function takeScreenShot($filename) {
    $screenshot = $this->driver->takeScreenshot();
    file_put_contents('log/'.$filename.'.png', $screenshot);
  }

  public function setUp() {
    $this->driver = RemoteWebDriver::create(self::getSeleniumUrl(), DesiredCapabilities::phantomjs());
    $this->setDisplay('large');
    parent::setUp();
  }

  protected function setDisplay($size) {
    if (isset(self::$displayWidths[$size])) {
      $this->setWindowSize(self::$displayWidths[$size], self::$height);
    }
  }

  protected function setWindowSize($width, $height) {
    $this->driver->manage()->window()->setSize(new WebDriverDimension($width, $height));
  }

  /**
   * Wait
   * @param $ms
   */
  protected function wait($seconds) {
    $this->driver->manage()->timeouts()->implicitlyWait($seconds);
  }

  /**
   * Get the DOM element matching the given xpath
   * @param $xpath The xpath
   * @return element|false
   */
  protected function byXpath($xpath) {
    return $this->driver->findElement(WebDriverBy::xpath($xpath));
  }

  /**
   * Log into the application
   * @param $user The username
   * @param $password The password
   */
  protected function login($user, $password) {
    $this->driver->get(self::getAppUrl());
    $this->driver->wait(10, 1000)->until(
      WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('user'))
    );
    $this->takeScreenShot('login');
    $this->driver->findElement(WebDriverBy::name('user'))->sendKeys($user);
    $this->driver->findElement(WebDriverBy::name('password'))->sendKeys($password);
    $this->byXpath("//span[contains(text(),'Sign in')]")->click();
  }
}
?>