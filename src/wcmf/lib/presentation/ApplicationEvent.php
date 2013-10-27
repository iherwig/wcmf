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
namespace wcmf\lib\presentation;

use wcmf\lib\core\Event;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * ApplicationEvent instances are fired at different stages
 * of the program flow. Note that depending on the stage, some of
 * the properties may be null, because they are not initialized yet
 * (e.g. controller).
 * Listening to this application events allows users to intercept
 * the application flow and change it by modifying the requests
 * and responses.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ApplicationEvent extends Event {

  const NAME = __CLASS__;

  /**
   * A BEFORE_ROUTE_ACTION event occurs before the request is mapped to
   * an action key. The request data are in the original format at
   * this stage.
   */
  const BEFORE_ROUTE_ACTION = 'BEFORE_ROUTE_ACTION';

  /**
   * A BEFORE_INITIALIZE_CONTROLLER event occurs before the current
   * controller is initialized.
   */
  const BEFORE_INITIALIZE_CONTROLLER = 'BEFORE_INITIALIZE_CONTROLLER';

  /**
   * A BEFORE_EXECUTE_CONTROLLER event occurs after the current
   * controller is initialized and before it is executed.
   */
  const BEFORE_EXECUTE_CONTROLLER = 'BEFORE_EXECUTE_CONTROLLER';

  /**
   * A AFTER_EXECUTE_CONTROLLER event occurs after the current
   * controller is executed.
   */
  const AFTER_EXECUTE_CONTROLLER = 'AFTER_EXECUTE_CONTROLLER';

  private $_stage = null;
  private $_request = null;
  private $_response = null;
  private $_controller = null;

  /**
   * Constructor.
   * @param state The stage at which the event occured.
   * @param request The request instance.
   * @param response The response instance (optional).
   * @param controller The controller instance (optional).
   */
  public function __construct($stage, Request $request, Response $response=null, Controller $controller=null) {
    $this->_stage = $stage;
    $this->_request = $request;
    $this->_response = $response;
    $this->_controller = $controller;
  }

  /**
   * Get the stage at which the event occured.
   * @return String
   */
  public function getStage() {
    return $this->_stage;
  }
  /**
   * Get the request.
   * @return Request instance
   */
  public function getRequest() {
    return $this->_request;
  }

  /**
   * Get the response.
   * @return Response instance
   */
  public function getResponse() {
    return $this->_response;
  }

  /**
   * Get the controller.
   * @return Controller instance
   */
  public function getController() {
    return $this->_controller;
  }
}
?>
