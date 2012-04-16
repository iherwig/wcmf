<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace test\tests\core;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\output\LogOutputStrategy;

/**
 * JSONFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectFactoryTest extends \PHPUnit_Framework_TestCase {

  public function testDIShared() {
    $obj = ObjectFactory::getInstance('persistenceFacade');
    $this->assertEquals('wcmf\lib\persistence\PersistenceFacadeImpl', get_class($obj));
    $this->assertFalse($obj->isLogging());

    // modify instance
    $obj->enableLogging(new LogOutputStrategy());
    $this->assertTrue($obj->isLogging());

    // get second time (same instance)
    $obj2 = ObjectFactory::getInstance('persistenceFacade');
    $this->assertEquals('wcmf\lib\persistence\PersistenceFacadeImpl', get_class($obj2));
    $this->assertTrue($obj2->isLogging());
  }

  public function testDINonShared() {
    $obj = ObjectFactory::getInstance('view');
    $this->assertEquals('wcmf\lib\presentation\SmartyView', get_class($obj));

    // modify instance
    $obj->assign('test', 'value1');
    $this->assertEquals('value1', $obj->getTemplateVars('test'));

    // get second time (same instance)
    $obj2 = ObjectFactory::getInstance('view');
    $this->assertEquals('wcmf\lib\presentation\SmartyView', get_class($obj2));

    // modify instance
    $obj2->assign('test', 'value2');
    $this->assertEquals('value2', $obj2->getTemplateVars('test'));

    // check first instance
    $this->assertEquals('value1', $obj->getTemplateVars('test'));
  }

  public function test() {
    $obj = ObjectFactory::getInstance('controlRenderer');
    $this->assertEquals('wcmf\lib\presentation\control\ControlRenderer', get_class($obj));
    $ctrl = $obj->getControl('text');
    $this->assertEquals('wcmf\lib\presentation\control\BaseControl', get_class($ctrl));
  }
}
?>