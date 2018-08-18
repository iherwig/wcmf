<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\config;

use wcmf\lib\config\ConfigChangeEvent;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\test\lib\BaseTestCase;

/**
 * InifileConfigurationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InifileConfigurationTest extends BaseTestCase {

  const INI_FILE = WCMF_BASE.'app/config/config.ini';
  const CONFIG_CACHE_PATH = WCMF_BASE.'app/config/cache';

  protected function setUp() {
    FileUtil::mkdirRec(self::CONFIG_CACHE_PATH);
    FileUtil::emptyDir(self::CONFIG_CACHE_PATH);
    parent::setUp();
  }

  protected function tearDown() {
    FileUtil::emptyDir(self::CONFIG_CACHE_PATH);
    parent::tearDown();
  }

  public function testConfigFileNotChanged() {
    $config = ObjectFactory::getInstance('configuration');
    $config->addConfiguration('config.ini');

    $hasChanged = false;
    ObjectFactory::getInstance('eventManager')->addListener(ConfigChangeEvent::NAME, function($event) use (&$hasChanged) {
      $hasChanged = true;
    });
    sleep(2);

    // test
    $config->addConfiguration('config.ini');
    $this->assertFalse($hasChanged);
  }

  public function testConfigFileChanged() {
    $config = ObjectFactory::getInstance('configuration');
    $config->addConfiguration('config.ini');

    $hasChanged = false;
    ObjectFactory::getInstance('eventManager')->addListener(ConfigChangeEvent::NAME, function($event) use (&$hasChanged) {
      $hasChanged = true;
    });
    sleep(2);

    $this->changeInifile(self::INI_FILE);

    // test
    $config->addConfiguration('config.ini');
    $this->assertTrue($hasChanged);

    $this->resetInifile(self::INI_FILE);
  }

  private function changeInifile($file) {
    $content = file_get_contents($file);
    $content .= "\n; test";
    file_put_contents($file, $content);
  }

  private function resetInifile($file) {
    $content = file_get_contents($file);
    $content = preg_replace("/\n; test/", "", $content);
    file_put_contents($file, $content);
  }
}
?>