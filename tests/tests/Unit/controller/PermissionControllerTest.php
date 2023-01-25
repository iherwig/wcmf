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
namespace wcmf\test\tests\controller;

use wcmf\test\lib\ArrayDataSet;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\security\AuthorizationException;
use wcmf\lib\util\TestUtil;

/**
 * PermissionControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionControllerTest extends \Codeception\Test\Unit {

  const TEST_TYPE = 'User';
  const TEST_OID = 'User:1';

  const CONTROLLER = 'wcmf\application\controller\PermissionController';

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => 'permissions.ini'],
        ['id' => 1, 'login' => 'userPermTest', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02', 'active' => 1, 'super_user' => 0, 'config' => 'permissions.ini']
      ],
      'NMUserRole' => [
        ['fk_user_id' => 0, 'fk_role_id' => 0],
        ['fk_user_id' => 1, 'fk_role_id' => 1],
      ],
      'Role' => [
        ['id' => 0, 'name' => 'administrators'],
        ['id' => 1, 'name' => 'tester'],
      ],
      'Permission' => [
        ['id' => 111, 'resource' => 'Chapter:111', 'context' => 'test', 'action' => 'delete', 'roles' => '+* +users -administrators'],
      ],
      'Chapter' => [
        ['id' => 111],
      ]
    ]);
  }

  /**
   * @group controller
   */
  public function testCheckPermissionsAdmin() {
    TestUtil::startSession('admin', 'admin');

    $operations = [
      'app.src.model.wcmf.User??read',
      'app.src.model.wcmf.User??update',
      'app.src.model.wcmf.User??create',
      'app.src.model.wcmf.User??delete',
      'app.src.model.Author??read',
      'app.src.model.Author??update',
      'app.src.model.Author??create',
      'app.src.model.Author??delete',
      'app.src.model.Publisher??read',
      'app.src.model.Publisher??update',
      'app.src.model.Publisher??create',
      'app.src.model.Publisher??delete',
    ];

    // simulate check permissions call
    $data = [
      'operations' => $operations
    ];
    $response = $this->tester->runRequest('checkPermissions', self::CONTROLLER, $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(12, sizeof($result));

    $this->assertTrue($result['app.src.model.wcmf.User??read']);
    $this->assertTrue($result['app.src.model.wcmf.User??update']);
    $this->assertTrue($result['app.src.model.wcmf.User??create']);
    $this->assertTrue($result['app.src.model.wcmf.User??delete']);
    $this->assertTrue($result['app.src.model.Author??read']);
    $this->assertFalse($result['app.src.model.Author??update']);
    $this->assertTrue($result['app.src.model.Author??create']);
    $this->assertTrue($result['app.src.model.Author??delete']);
    $this->assertTrue($result['app.src.model.Publisher??read']);
    $this->assertTrue($result['app.src.model.Publisher??update']);
    $this->assertTrue($result['app.src.model.Publisher??create']);
    $this->assertTrue($result['app.src.model.Publisher??delete']);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testCheckPermissionsTester() {
    TestUtil::startSession('admin', 'admin');

    $operations = [
      'app.src.model.wcmf.User??read',
      'app.src.model.wcmf.User??update',
      'app.src.model.wcmf.User??create',
      'app.src.model.wcmf.User??delete',
      'app.src.model.Author??read',
      'app.src.model.Author??update',
      'app.src.model.Author??create',
      'app.src.model.Author??delete',
      'app.src.model.Publisher??read',
      'app.src.model.Publisher??update',
      'app.src.model.Publisher??create',
      'app.src.model.Publisher??delete',
    ];

    // simulate check permissions call
    $data = [
      'operations' => $operations,
      'user' => 'userPermTest'
    ];
    $response = $this->tester->runRequest('checkPermissionsOfUser', self::CONTROLLER, $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(12, sizeof($result));

    $this->assertTrue($result['app.src.model.wcmf.User??read']);
    $this->assertFalse($result['app.src.model.wcmf.User??update']);
    $this->assertFalse($result['app.src.model.wcmf.User??create']);
    $this->assertFalse($result['app.src.model.wcmf.User??delete']);
    $this->assertTrue($result['app.src.model.Author??read']);
    $this->assertFalse($result['app.src.model.Author??update']);
    $this->assertTrue($result['app.src.model.Author??create']);
    $this->assertTrue($result['app.src.model.Author??delete']);
    $this->assertTrue($result['app.src.model.Publisher??read']);
    $this->assertTrue($result['app.src.model.Publisher??update']);
    $this->assertTrue($result['app.src.model.Publisher??create']);
    $this->assertTrue($result['app.src.model.Publisher??delete']);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testGetPermissions() {
    TestUtil::startSession('admin', 'admin');

    // simulate get permissions call
    $data = [
      'operation' => 'app.src.model.wcmf.User??read'
    ];
    $response = $this->tester->runRequest('getPermissions', self::CONTROLLER, $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(2, sizeof($result['allow']));

    $this->assertEquals('administrators', $result['allow'][0]);
    $this->assertEquals('tester', $result['allow'][1]);
    $this->assertFalse($result['default']);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testSetPermissions() {
    TestUtil::startSession('admin', 'admin');

    // simulate set permissions call
    $data = [
      'operation' => 'app.src.model.wcmf.User??read',
      'permissions' => [
        'allow' => ['administrators'],
        'deny' => ['tester'],
        'default' => true
      ]
    ];
    $response = $this->tester->runRequest('setPermissions', self::CONTROLLER, $data);

    // test
    $data = [
      'operation' => 'app.src.model.wcmf.User??read'
    ];
    $response = $this->tester->runRequest('getPermissions', self::CONTROLLER, $data);
    $result = $response->getValue('result');
    $this->assertEquals(1, sizeof($result['allow']));
    $this->assertEquals('administrators', $result['allow'][0]);
    $this->assertEquals(1, sizeof($result['deny']));
    $this->assertEquals('tester', $result['deny'][0]);
    $this->assertTrue($result['default']);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testSetPermissionsNull() {
    TestUtil::startSession('admin', 'admin');

    // simulate get permissions call
    $data = [
      'operation' => 'Chapter:111?test?delete'
    ];
    $response = $this->tester->runRequest('getPermissions', self::CONTROLLER, $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(1, sizeof($result['allow']));
    $this->assertEquals(1, sizeof($result['deny']));
    $this->assertTrue($result['default']);

    // simulate set permissions call
    $data = [
      'operation' => 'Chapter:111?test?delete',
      'permissions' => null
    ];
    $response = $this->tester->runRequest('setPermissions', self::CONTROLLER, $data);

    // test
    $data = [
      'operation' => 'Chapter:111?test?delete'
    ];
    $response = $this->tester->runRequest('getPermissions', self::CONTROLLER, $data);
    $result = $response->getValue('result');
    $this->assertNull($result);

    TestUtil::endSession();
  }

  /**
   * @group controller
   */
  public function testDeletePermissions() {
    TestUtil::startSession('admin', 'admin');

    $oid = 'app.src.model.Chapter:111';

    // simulate get permissions of user call
    $data = [
      'operations' => [$oid.'??delete'],
      'user' => 'admin'
    ];
    $response = $this->tester->runRequest('checkPermissionsOfUser', self::CONTROLLER, $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(1, sizeof($result));
    $this->assertTrue($result['app.src.model.Chapter:111??delete']);

    // simulate set permissions call
    $data = [
      'operation' => $oid.'??delete',
      'permissions' => [
        'allow' => [],
        'deny' => ['administrators'],
        'default' => true
      ]
    ];
    $response = $this->tester->runRequest('setPermissions', self::CONTROLLER, $data);

    // test
    // simulate get permissions of user call
    $data = [
      'operations' => [$oid.'??delete'],
      'user' => 'admin'
    ];
    $response = $this->tester->runRequest('checkPermissionsOfUser', self::CONTROLLER, $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(1, sizeof($result));
    $this->assertFalse($result['app.src.model.Chapter:111??delete']);

    // test
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $chapter = $persistenceFacade->load(new ObjectId('Chapter', 111));
    $this->assertNotNull($chapter);
    $chapter->delete();
    try {
      $transaction->commit();
      $this->fail('An expected exception has not been raised.');
    } catch (AuthorizationException $ex) {
      $transaction->rollback();
    }
    $oids = $persistenceFacade->getOIDs('app.src.model.Chapter');
    $this->assertTrue(in_array($oid, $oids), $oid." still exists");

    TestUtil::endSession();
  }
}
?>