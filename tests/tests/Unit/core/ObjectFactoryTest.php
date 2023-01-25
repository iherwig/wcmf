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
namespace tests\core;

use wcmf\lib\core\ObjectFactory;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;

/**
 * ObjectFactoryTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactoryTest extends \Codeception\Test\Unit {

  public function testShared(): void {
    $conf = ObjectFactory::getInstance('configuration');
    assertInstanceOf(\wcmf\lib\config\impl\InifileConfiguration::class, $conf);
    assertThat($conf->getValue('title', 'Application'), equalTo('WCMF TEST MODEL'));

    // modify instance
    $conf->setValue('title', 'WCMF TEST MODEL2', 'Application');
    assertThat($conf->getValue('title', 'Application'), equalTo('WCMF TEST MODEL2'));

    // get second time (same instance)
    $conf2 = ObjectFactory::getInstance('configuration');
    assertInstanceOf(\wcmf\lib\config\impl\InifileConfiguration::class, $conf2);
    assertThat($conf->getValue('title', 'Application'), equalTo('WCMF TEST MODEL2'));
  }

  public function testNonShared(): void {
    $conf = ObjectFactory::getInstance('configuration');
    $conf->addConfiguration('factory.ini', true);

    $obj = ObjectFactory::getInstance('request');
    assertInstanceOf(\wcmf\lib\presentation\impl\DefaultRequest::class, $obj);

    // modify instance
    $obj->setValue('test', 'value1');
    assertThat($obj->getValue('test'), equalTo('value1'));

    // get second time (same instance)
    $obj2 = ObjectFactory::getInstance('request');
    assertInstanceOf(\wcmf\lib\presentation\impl\DefaultRequest::class, $obj2);

    // modify instance
    $obj2->setValue('test', 'value2');
    assertThat($obj2->getValue('test'), equalTo('value2'));

    // check first instance
    assertThat($obj->getValue('test'), equalTo('value1'));
  }

  public function testAlias(): void {
    $conf = ObjectFactory::getInstance('configuration');
    $conf->addConfiguration('factory.ini', true);

    // get second time (same instance)
    $cache = ObjectFactory::getInstance('filecache');
    assertInstanceOf(\wcmf\lib\io\impl\FileCache::class, $cache);
  }
}
?>