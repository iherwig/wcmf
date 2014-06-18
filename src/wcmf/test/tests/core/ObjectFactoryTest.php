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
namespace wcmf\test\tests\core;

use wcmf\test\lib\BaseTestCase;
use wcmf\lib\core\ObjectFactory;

/**
 * ObjectFactoryTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactoryTest extends BaseTestCase {

  public function testDIShared() {
    $obj = ObjectFactory::getInstance('persistenceFacade');
    $this->assertEquals('wcmf\lib\persistence\impl\DefaultPersistenceFacade', get_class($obj));
    $this->assertFalse($obj->isLogging());

    // modify instance
    $obj->setLogging(true);
    $this->assertTrue($obj->isLogging());

    // get second time (same instance)
    $obj2 = ObjectFactory::getInstance('persistenceFacade');
    $this->assertEquals('wcmf\lib\persistence\impl\DefaultPersistenceFacade', get_class($obj2));
    $this->assertTrue($obj2->isLogging());
  }

  public function testDINonShared() {
    $obj = ObjectFactory::getInstance('view');
    $this->assertEquals('wcmf\lib\presentation\view\impl\SmartyView', get_class($obj));

    // modify instance
    $obj->setValue('test', 'value1');
    $this->assertEquals('value1', $obj->getValue('test'));

    // get second time (same instance)
    $obj2 = ObjectFactory::getInstance('view');
    $this->assertEquals('wcmf\lib\presentation\view\impl\SmartyView', get_class($obj2));

    // modify instance
    $obj2->setValue('test', 'value2');
    $this->assertEquals('value2', $obj2->getValue('test'));

    // check first instance
    $this->assertEquals('value1', $obj->getValue('test'));
  }
}
?>