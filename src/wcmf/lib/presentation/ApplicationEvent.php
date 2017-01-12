<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
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

  private $stage = null;
  private $request = null;
  private $response = null;
  private $controller = null;

  /**
   * Constructor.
   * @param $stage The stage at which the event occured.
   * @param $request The request instance.
   * @param $response The response instance (optional).
   * @param $controller The controller instance (optional).
   */
  public function __construct($stage, Request $request, Response $response=null, Controller $controller=null) {
    $this->stage = $stage;
    $this->request = $request;
    $this->response = $response;
    $this->controller = $controller;
  }

  /**
   * Get the stage at which the event occured.
   * @return String
   */
  public function getStage() {
    return $this->stage;
  }
  /**
   * Get the request.
   * @return Request instance
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the response.
   * @return Response instance
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Get the controller.
   * @return Controller instance
   */
  public function getController() {
    return $this->controller;
  }
}
?>
