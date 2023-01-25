<?php
namespace Tests\Support\Helper;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Response;
use wcmf\lib\util\TestUtil;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Controller extends \Codeception\Module
{
  /**
   * Make a request to a controller. This method makes sure that the
   * requested action is routed to the controller to be tested.
   * The calling method has to make sure that a session is started, if necessary
   * (e.g. by calling TestUtil::startSession()). The transaction will be rolled
   * back before the request is run in order to avoid side effects.
   * @param $action The action
   * @param $controller The controller
   * @param $data An associative array with additional key/value pairs for the Request instance
   * @param $addActionKey Boolean, whether to add an action key for the given action to the configuration or not (optional, default: _true_)
   * @return Response instance
   */
  public function runRequest(string $action, string $controller, array $data, bool $addActionKey=true): Response {
    return $this->runInternal($action, $controller, $data, $addActionKey, false);
  }

  /**
   * Make a request to the controller with the controller set as sender.
   * @see ControllerTestCase::runRequest()
   */
  public function runRequestFromThis(string $action, string $controller, array $data, bool $addActionKey=true): Response {
    return $this->runInternal($action, $controller, $data, $addActionKey, true);
  }

  /**
   * Make a request to the controller.
   * @param $action The action
   * @param $controller The controller
   * @param $data An associative array with additional key/value pairs for the Request instance
   * @parma $addActionKey Boolean, whether to add an action key for the given action to the configuration or not (optional, default: _true_)
   * @parma $addSender Boolean, whether to add the controller as sender or not (optional, default: _false_)
   * @return Response instance
   */
  private function runInternal(string $action, string $controller, array $data, bool $addActionKey=true, bool $addSender=false): Response {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $persistenceFacade->getTransaction()->rollback();

    // add action key
    if ($addActionKey) {
      TestUtil::setConfigValue('??'.$action, $controller, 'actionmapping');
    }

    // make request
    $request = ObjectFactory::getNewInstance('request');
    $request->setAction($action);
    if ($addSender) {
      $request->setSender($controller);
    }
    foreach ($data as $key => $value) {
      $request->setValue($key, $value);
    }
    return TestUtil::simulateRequest($request);
  }
}
