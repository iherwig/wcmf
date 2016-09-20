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
namespace wcmf\test\tests\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;

/**
 * BatchControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BatchControllerTest extends ControllerTestCase {

  protected function getControllerName() {
    return 'wcmf\application\controller\SimpleBatchController';
  }

  protected function setUp() {
    parent::setUp();
    $conf = ObjectFactory::getInstance('configuration');
    $conf->addConfiguration('controller.ini', true);
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('table' => ''),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'config' => ''),
      ),
    ));
  }

  /**
   * @group controller
   */
  public function testProcess() {
    TestUtil::startSession('admin', 'admin');

    $expectations = array(
        // next action, step number, number of steps, display text for next step, result value from controller, result action, file in response
        array('simpleBatch', 0, 5, 'Package 1 1-2/5 ...', null, 'progress', false),
        array('continue', 1, 5, 'Package 1 3-4/5 ...', 'P1-1,2', 'progress', false),
        array('continue', 2, 5, 'Package 1 5/5 ...', 'P1-3,4', 'progress', false),
        array('continue', 3, 5, 'Package 2 1-3/5 ...', 'P1-5', 'progress', false),
        array('continue', 4, 5, 'Package 2 4-5/5 ...', 'P2-6,7,8', 'progress', false),
        array('continue', 5, 5, 'Done', 'P2-9,10', 'done', false),
    );
    $data = array();
    $this->process($expectations, $data);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testProcessWithDownload() {
    TestUtil::startSession('admin', 'admin');

    $expectations = array(
        // next action, step number, number of steps, display text for next step, result value from controller, result action, file in response
        array('simpleBatch', 0, 5, 'Package 1 1-2/5 ...', null, 'progress', false),
        array('continue', 1, 5, 'Package 1 3-4/5 ...', 'P1-1,2', 'progress', false),
        array('continue', 2, 5, 'Package 1 5/5 ...', 'P1-3,4', 'progress', false),
        array('continue', 3, 5, 'Package 2 1-3/5 ...', 'P1-5', 'progress', false),
        array('continue', 4, 5, 'Package 2 4-5/5 ...', 'P2-6,7,8', 'progress', false),
        array('continue', 5, 5, 'Done', 'P2-9,10', 'download', false),
        array('continue', null, null, null, null, 'done', true),
    );
    $data = array('download' => true);
    $this->process($expectations, $data);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testOneCall() {
    TestUtil::startSession('admin', 'admin');

    $expectations = array(
        // next action, step number, number of steps, display text for next step, result value from controller, result action, file in response
        array('simpleBatch', 1, 5, 'Package 1 3-4/5 ...', 'P1-1,2', 'done', false),
    );
    $data = array('oneCall' => true);
    $this->process($expectations, $data);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testOneCallWithDownload() {
    TestUtil::startSession('admin', 'admin');

    $expectations = array(
        // next action, step number, number of steps, display text for next step, result value from controller, result action, file in response
        array('simpleBatch', 1, 5, 'Package 1 3-4/5 ...', 'P1-1,2', 'download', false),
        array('continue', null, null, null, null, 'done', true),
    );
    $data = array('oneCall' => true, 'download' => true);
    $this->process($expectations, $data);

    TestUtil::endSession();
  }

  private function process($expectations, $data) {
    // simulate calls
    for($i=0, $count=sizeof($expectations); $i<$count; $i++) {
      $expectation = $expectations[$i];
      if ($i==0) {
        $response = $this->runRequest($expectation[0], $data, false);
      }
      else {
        $response = $this->runRequestFromThis($expectation[0], $data, false);
      }
      // test
      $this->checkExpectation($expectation, $response);
    }
  }

  private function checkExpectation($expectation, $response) {
    $file = $response->getFile();

    $this->assertEquals($expectation[1], $response->getValue('stepNumber'));
    $this->assertEquals($expectation[2], $response->getValue('numberOfSteps'));
    $this->assertEquals($expectation[3], $response->getValue('displayText'));
    $this->assertEquals($expectation[4], $response->getValue('result'));
    $this->assertEquals($expectation[5], $response->getValue('status'));
    $this->assertEquals($expectation[5], $response->getAction());
    $this->assertEquals($expectation[6], $file !== null);
    if ($file) {
      $this->assertEquals(1, preg_match('/class SimpleBatchController extends BatchController/', $file['content']));
    }
  }
}
?>