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
namespace wcmf\test\lib;

use wcmf\test\lib\DatabaseTestCase;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\TestUtil;

/**
 * ControllerTestCase is the base class for test cases used for Controllers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class ControllerTestCase extends DatabaseTestCase {

  /**
   * Make a request to the controller. This method makes sure that the
   * requested action is routed to the controller to be tested.
   * The calling method has to make sure that a session is started, if necessary
   * (e.g. by calling TestUtil::startSession()). The transaction will be rolled
   * back before the request is run in order to avoid side effects.
   * @param $action The action
   * @param $data An associative array with additional key/value pairs for the Request instance
   * @parma $addActionKey Boolean, whether to add an action key for the given action to the configuration or not (optional, default: _true_)
   * @return Response instance
   */
  protected function runRequest($action, $data, $addActionKey=true) {
    return $this->runInternal($action, $data, $addActionKey, false);
  }

  /**
   * Make a request to the controller with the controller set as sender.
   * @see ControllerTestCase::runRequest()
   */
  protected function runRequestFromThis($action, $data, $addActionKey=true) {
    return $this->runInternal($action, $data, $addActionKey, true);
  }

  /**
   * Make a request to the controller.
   * @param $action The action
   * @param $data An associative array with additional key/value pairs for the Request instance
   * @parma $addActionKey Boolean, whether to add an action key for the given action to the configuration or not (optional, default: _true_)
   * @parma $addSender Boolean, whether to add the controller as sender or not (optional, default: _false_)
   * @return Response instance
   */
  private function runInternal($action, $data, $addActionKey=true, $addSender=false) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $persistenceFacade->getTransaction()->rollback();

    // add action key
    if ($addActionKey) {
      TestUtil::setConfigValue('??'.$action, $this->getControllerName(), 'actionmapping');
    }

    // make request
    $request = ObjectFactory::getInstance('request');
    $request->setAction($action);
    if ($addSender) {
      $request->setSender($this->getControllerName());
    }
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