<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\config;

use wcmf\lib\config\ConfigChangeEvent;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\ObjectFactory;
use wcmf\test\lib\BaseTestCase;

/**
 * InifileConfigurationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InifileConfigurationTest extends BaseTestCase {

  const INI_FILE = WCMF_BASE.'app/config/config.ini';

  public function testConfigFileNotChanged() {
    $config = ObjectFactory::getInstance('configuration');
    $testConfig = new InifileConfiguration($config->getConfigPath());
    $testConfig->addConfiguration('config.ini');

    $hasChanged = false;
    ObjectFactory::getInstance('eventManager')->addListener(ConfigChangeEvent::NAME, function($event) use (&$hasChanged) {
      $hasChanged = true;
    });

    // test
    $testConfig->addConfiguration('config.ini');
    $this->assertFalse($hasChanged);
  }

  public function testConfigFileChanged() {
    $config = ObjectFactory::getInstance('configuration');
    $testConfig = new InifileConfiguration($config->getConfigPath());
    $testConfig->addConfiguration('config.ini');

    $hasChanged = false;
    ObjectFactory::getInstance('eventManager')->addListener(ConfigChangeEvent::NAME, function($event) use (&$hasChanged) {
      $hasChanged = true;
    });

    $this->changeInifile(self::INI_FILE);

    // test
    $testConfig->addConfiguration('config.ini');
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