<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\controller;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\ControllerTestCase;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\security\AuthorizationException;
use wcmf\lib\util\TestUtil;

/**
 * PermissionControllerTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionControllerTest extends ControllerTestCase {

  const TEST_TYPE = 'User';
  const TEST_OID = 'User:1';

  protected function getControllerName() {
    return 'wcmf\application\controller\PermissionController';
  }

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'config' => 'permissions.ini'),
        array('id' => 1, 'login' => 'userPermTest', 'password' => '$2y$10$iBjiDZ8XyK1gCOV6m5lbO.2ur42K7M1zSpm.NU7u5g3mYTi2kiu02', 'config' => 'permissions.ini')
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
        array('fk_user_id' => 1, 'fk_role_id' => 1),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
        array('id' => 1, 'name' => 'tester'),
      ),
      'Permission' => array(
        array('id' => 111, 'resource' => 'Chapter:111', 'context' => 'test', 'action' => 'delete', 'roles' => '+* +users -administrators'),
      ),
      'Chapter' => array(
        array('id' => 111),
      )
    ));
  }

  /**
   * @group controller
   */
  public function testCheckPermissionsAdmin() {
    TestUtil::startSession('admin', 'admin');

    $operations = array(
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
    );

    // simulate check permissions call
    $data = array(
      'operations' => $operations
    );
    $response = $this->runRequest('checkPermissions', $data);

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

    $operations = array(
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
    );

    // simulate check permissions call
    $data = array(
      'operations' => $operations,
      'user' => 'userPermTest'
    );
    $response = $this->runRequest('checkPermissionsOfUser', $data);

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
    $data = array(
      'resource' => 'app.src.model.wcmf.User',
      'context' => '',
      'action' => 'read'
    );
    $response = $this->runRequest('getPermissions', $data);

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
    $data = array(
      'resource' => 'app.src.model.wcmf.User',
      'context' => '',
      'action' => 'read',
      'permissions' => array(
        'allow' => array('administrators'),
        'deny' => array('tester'),
        'default' => true
      )
    );
    $response = $this->runRequest('setPermissions', $data);

    // test
    $data = array(
      'resource' => 'app.src.model.wcmf.User',
      'context' => '',
      'action' => 'read'
    );
    $response = $this->runRequest('getPermissions', $data);
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
    $data = array(
      'resource' => 'Chapter:111',
      'context' => 'test',
      'action' => 'delete'
    );
    $response = $this->runRequest('getPermissions', $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(1, sizeof($result['allow']));
    $this->assertEquals(1, sizeof($result['deny']));
    $this->assertTrue($result['default']);

    // simulate set permissions call
    $data = array(
      'resource' => 'Chapter:111',
      'context' => 'test',
      'action' => 'delete',
      'permissions' => null
    );
    $response = $this->runRequest('setPermissions', $data);

    // test
    $data = array(
      'resource' => 'Chapter:111',
      'context' => 'test',
      'action' => 'delete'
    );
    $response = $this->runRequest('getPermissions', $data);
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
    $data = array(
      'operations' => array($oid.'??delete'),
      'user' => 'admin'
    );
    $response = $this->runRequest('checkPermissionsOfUser', $data);

    // test
    $result = $response->getValue('result');
    $this->assertEquals(1, sizeof($result));
    $this->assertTrue($result['app.src.model.Chapter:111??delete']);

    // simulate set permissions call
    $data = array(
      'resource' => $oid,
      'context' => '',
      'action' => 'delete',
      'permissions' => array(
        'allow' => array(),
        'deny' => array('administrators'),
        'default' => true
      )
    );
    $response = $this->runRequest('setPermissions', $data);

    // test
    // simulate get permissions of user call
    $data = array(
      'operations' => array($oid.'??delete'),
      'user' => 'admin'
    );
    $response = $this->runRequest('checkPermissionsOfUser', $data);

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