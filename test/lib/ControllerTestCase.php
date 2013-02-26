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
namespace test\lib;

use test\lib\DatabaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\presentation\Request;


/**
 * ControllerTestCase is a PHPUnit test case, that
 * serves as base class for test cases used for Controllers.
 * The application is configured to
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class ControllerTestCase extends DatabaseTestCase {

  /**
   * Make a request to the controller. This method makes sure that the
   * requested action is routed to the controller to be tested.
   * The calling method has to make sure that a session is started, if necessary
   * (e.g. by calling TestUtil::startSession()).
   * @param data An associative array with additional key/value pairs for the Request instance
   * @return Response instance
   */
  protected function runRequest($action, $data) {
    // add action key
    TestUtil::setConfigValue('??'.$action, $this->getControllerName(), 'actionmapping');

    // make request
    $request = new Request('\wcmf\application\controller\TerminateController', '', $action);
    foreach ($data as $key => $value) {
      $request->setValue($key, $value);
    }
    return TestUtil::simulateRequest($request);
  }

  /**
   * Get the fully qualified name of the controller to test
   * @return The name of the controller
   */
  abstract protected function getControllerName();
}
?>