<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace tests\config;

use wcmf\lib\config\ConfigChangeEvent;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;

/**
 * InifileConfigurationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InifileConfigurationTest extends \Codeception\Test\Unit {

  const INI_FILE = WCMF_BASE.'app/config/config.ini';
  const CONFIG_CACHE_PATH = WCMF_BASE.'app/config/cache';

  protected function _before() {
    FileUtil::mkdirRec(self::CONFIG_CACHE_PATH);
    FileUtil::emptyDir(self::CONFIG_CACHE_PATH);
  }

  protected function _after() {
    FileUtil::emptyDir(self::CONFIG_CACHE_PATH);
    rmdir(self::CONFIG_CACHE_PATH);
  }

  public function testConfigFileNotChanged(): void {
    $config = ObjectFactory::getInstance('configuration');
    $config->addConfiguration('config.ini');

    $hasChanged = false;
    ObjectFactory::getInstance('eventManager')->addListener(ConfigChangeEvent::NAME, function($event) use (&$hasChanged) {
      $hasChanged = true;
    });
    sleep(2);

    // test
    $config->addConfiguration('config.ini');
    $this->assertThat($hasChanged, $this->isFalse());
  }

  public function testConfigFileChanged(): void {
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
    $this->assertThat($hasChanged, $this->isTrue());

    $this->resetInifile(self::INI_FILE);
  }

  private function changeInifile($file): void {
    $content = file_get_contents($file);
    $content .= "\n; test";
    file_put_contents($file, $content);
  }

  private function resetInifile($file): void {
    $content = file_get_contents($file);
    $content = preg_replace("/\n; test/", "", $content);
    file_put_contents($file, $content);
  }
}
?>