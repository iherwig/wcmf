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
 * $Id: WCMFTestCase.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once("lib/WCMFTestCase.php");

/**
 * @class ControllerTestCase
 * @ingroup test
 * @brief ControllerTestCase is a PHPUnit test case, that
 * serves as base class for test cases used for Controllers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class ControllerTestCase extends WCMFTestCase
{
  private $_sid = null;

  /**
   * Get the action name for this test.
   * @return The action name
   */
  private function getAction()
  {
    return 'test'.$this->getControllerName();
  }

  protected function setUp()
  {
    // log into the application
    $this->_sid = $this->startSession('admin', 'admin');
    
    // setup the test action mapping
    $this->setConfigValue('??'.$this->getAction(), $this->getControllerName(), 'actionmapping');
  }
  
  protected function tearDown()
  {
    // log out
    $this->endSession($this->_sid);
  }

  /**
   * Make a request to the controller.
   * @param data An associative array with additional key/value pairs for the Request instance
   * @return A reference to the Response
   */
  protected function &runRequest($data)
  {
    $request = new Request('TerminateController', '', $this->getAction(),
      array(
        'sid' => $this->_sid
      )
    );
    foreach ($data as $key => $value) {
      $request->setValue($key, $value);
    }
    return $this->simulateRequest($request);
  }
  
  /**
   * Get the name of the controller to test
   * @return The name of the controller
   */
  abstract protected function getControllerName();
}
?>