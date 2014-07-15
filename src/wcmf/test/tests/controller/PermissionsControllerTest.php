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
namespace wcmf\test\tests\controller;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;
use wcmf\test\lib\TestUtil;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

/**
 * PermissionsControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionsControllerTest extends ControllerTestCase {

  const TEST_TYPE = 'User';
  const TEST_OID = 'User:1';

  protected function getControllerName() {
    return 'wcmf\application\controller\PermissionsController';
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'config' => ''),
        array('id' => 1, 'login' => 'user1', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02', 'config' => 'permissions.ini')
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
        array('fk_user_id' => 1, 'fk_role_id' => 1),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
        array('id' => 1, 'name' => 'tester'),
      )
    ));
  }

  /**
   * @group controller
   */
  public function testAdmin() {
    TestUtil::startSession('admin', 'admin');
    
    $set = array(
      'app.src.model.wcmf.User??read',
      'app.src.model.wcmf.User??modify',
      'app.src.model.wcmf.User??create',
      'app.src.model.wcmf.User??delete',
      'app.src.model.Author??read',
      'app.src.model.Author??modify',
      'app.src.model.Author??create',
      'app.src.model.Author??delete',
      'app.src.model.Publisher??read',
      'app.src.model.Publisher??modify',
      'app.src.model.Publisher??create',
      'app.src.model.Publisher??delete',
    );

    // simulate check permissions call
    $data = array(
      'set' => $set
    );
    $response = $this->runRequest('checkpermissions', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $result = $response->getValue('result');
    $this->assertEquals(12, sizeof($result));
    
    $this->assertTrue($result['app.src.model.wcmf.User??read']);
    $this->assertTrue($result['app.src.model.wcmf.User??modify']);
    $this->assertTrue($result['app.src.model.wcmf.User??create']);
    $this->assertTrue($result['app.src.model.wcmf.User??delete']);
    $this->assertTrue($result['app.src.model.Author??read']);
    $this->assertTrue($result['app.src.model.Author??modify']);
    $this->assertTrue($result['app.src.model.Author??create']);
    $this->assertTrue($result['app.src.model.Author??delete']);
    $this->assertTrue($result['app.src.model.Publisher??read']);
    $this->assertTrue($result['app.src.model.Publisher??modify']);
    $this->assertTrue($result['app.src.model.Publisher??create']);
    $this->assertTrue($result['app.src.model.Publisher??delete']);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testTester() {
    TestUtil::startSession('user1', 'user1');
    
    $set = array(
      'app.src.model.wcmf.User??read',
      'app.src.model.wcmf.User??modify',
      'app.src.model.wcmf.User??create',
      'app.src.model.wcmf.User??delete',
      'app.src.model.Author??read',
      'app.src.model.Author??modify',
      'app.src.model.Author??create',
      'app.src.model.Author??delete',
      'app.src.model.Publisher??read',
      'app.src.model.Publisher??modify',
      'app.src.model.Publisher??create',
      'app.src.model.Publisher??delete',
    );

    // simulate check permissions call
    $data = array(
      'set' => $set
    );
    $response = $this->runRequest('checkpermissions', $data);

    // test
    $this->assertTrue($response->getValue('success'), 'The request was successful');
    $result = $response->getValue('result');
    $this->assertEquals(12, sizeof($result));

    $this->assertTrue($result['app.src.model.wcmf.User??read']);
    $this->assertFalse($result['app.src.model.wcmf.User??modify']);
    $this->assertFalse($result['app.src.model.wcmf.User??create']);
    $this->assertFalse($result['app.src.model.wcmf.User??delete']);
    $this->assertTrue($result['app.src.model.Author??read']);
    $this->assertFalse($result['app.src.model.Author??modify']);
    $this->assertTrue($result['app.src.model.Author??create']);
    $this->assertTrue($result['app.src.model.Author??delete']);
    $this->assertTrue($result['app.src.model.Publisher??read']);
    $this->assertTrue($result['app.src.model.Publisher??modify']);
    $this->assertTrue($result['app.src.model.Publisher??create']);
    $this->assertTrue($result['app.src.model.Publisher??delete']);
    
    TestUtil::endSession();
  }
}
?>