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
namespace wcmf\test\tests\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;

/**
 * ControllerChainTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ControllerChainTest extends ControllerTestCase {

  protected function getControllerName() {
    return 'app\src\controller\CalcController';
  }

  protected function setUp() {
    parent::setUp();
    $conf = ObjectFactory::getInstance('configuration');
    $conf->addConfiguration('controller.ini', true);
  }

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ],
    ]);
  }

  /**
   * @group controller
   */
  public function testSimple() {
    TestUtil::startSession('admin', 'admin');

    // simulate calc call
    $data = ['value' => 2];
    $response = $this->runRequest('calc', $data, false);

    // test
    $this->assertEquals(4, $response->getValue('value'));
    $this->assertEquals('ok', $response->getAction());
    $this->assertEquals('app\src\controller\CalcController::calcOk', trim($response->getValue('stack')));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testChain() {
    TestUtil::startSession('admin', 'admin');

    // simulate calc call
    $data = ['value' => 2];
    $response = $this->runRequest('calcChain', $data, false);

    // test
    $this->assertEquals(6, $response->getValue('value'));
    $this->assertEquals('ok', $response->getAction());
    $this->assertEquals('app\src\controller\CalcController::calcContinue app\src\controller\CalcController::calcOk', trim($response->getValue('stack')));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testChainBreak() {
    TestUtil::startSession('admin', 'admin');

    // simulate calc call
    $data = ['value' => 2];
    $response = $this->runRequest('calcChainBreak', $data, false);

    // test
    $this->assertEquals(4, $response->getValue('value'));
    $this->assertEquals(null, $response->getAction());
    $this->assertEquals('app\src\controller\CalcController::calcContinueSameActionKey', trim($response->getValue('stack')));

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testExecuteSubAction() {
    TestUtil::startSession('admin', 'admin');

    // simulate calc call
    $data = ['value' => 2];
    $response = $this->runRequest('calcExecuteSubAction', $data, false);

    // test
    $this->assertEquals(4, $response->getValue('value'));
    $this->assertEquals('ok', $response->getAction());
    $this->assertEquals('app\src\controller\CalcController::calcExecuteSubAction', trim($response->getValue('stack')));
    // NOTE sub request is based on original request
    $this->assertEquals(4, $response->getValue('subvalue'));
    $this->assertEquals('app\src\controller\CalcController::calcOk', trim($response->getValue('substack')));
    $this->assertEquals('ok', $response->getValue('subaction'));

    TestUtil::endSession();
  }

}
?>