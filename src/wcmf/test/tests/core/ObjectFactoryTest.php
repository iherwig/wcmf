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
namespace wcmf\test\tests\core;

use wcmf\test\lib\BaseTestCase;
use wcmf\lib\core\ObjectFactory;

/**
 * ObjectFactoryTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactoryTest extends BaseTestCase {

  public function testShared() {
    $conf = ObjectFactory::getInstance('configuration');
    $this->assertEquals('wcmf\lib\config\impl\InifileConfiguration', get_class($conf));
    $this->assertEquals('WCMF TEST MODEL', $conf->getValue('title', 'Application'));

    // modify instance
    $conf->setValue('title', 'WCMF TEST MODEL2', 'Application');
    $this->assertEquals('WCMF TEST MODEL2', $conf->getValue('title', 'Application'));

    // get second time (same instance)
    $conf2 = ObjectFactory::getInstance('configuration');
    $this->assertEquals('wcmf\lib\config\impl\InifileConfiguration', get_class($conf2));
    $this->assertEquals('WCMF TEST MODEL2', $conf->getValue('title', 'Application'));
  }

  public function testNonShared() {
    $conf = ObjectFactory::getInstance('configuration');
    $conf->addConfiguration('factory.ini', true);

    $obj = ObjectFactory::getInstance('request');
    $this->assertEquals('wcmf\lib\presentation\impl\DefaultRequest', get_class($obj));

    // modify instance
    $obj->setValue('test', 'value1');
    $this->assertEquals('value1', $obj->getValue('test'));

    // get second time (same instance)
    $obj2 = ObjectFactory::getInstance('request');
    $this->assertEquals('wcmf\lib\presentation\impl\DefaultRequest', get_class($obj2));

    // modify instance
    $obj2->setValue('test', 'value2');
    $this->assertEquals('value2', $obj2->getValue('test'));

    // check first instance
    $this->assertEquals('value1', $obj->getValue('test'));
  }

  public function testAlias() {
    $conf = ObjectFactory::getInstance('configuration');
    $conf->addConfiguration('factory.ini', true);

    // get second time (same instance)
    $cache = ObjectFactory::getInstance('filecache');
    $this->assertEquals('wcmf\lib\io\impl\FileCache', get_class($cache));
  }
}
?>