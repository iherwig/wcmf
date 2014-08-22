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
namespace wcmf\application\controller;

use wcmf\lib\i18n\Message;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * BatchController is used to process complex, longer running actions, that need
 * to be divided into several requests to overcome resource limits and provide
 * progress information to the user.
 *
 * Conceptionally the process is divided into subactions (_work packages_),
 * that are called sequentially. Depending on the progress, the controller sets
 * different actions on the response as result of one execution.
 *
 * BatchController only sets up the infrastructure, the concrete process is defined
 * by creating a subclass and implementing the abstract methods (mainly
 * BatchController::getWorkPackage()).
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Initialize the work packages and process the first action.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `oneCall`        | Boolean whether to accomplish the task in one call (optional, default: _false_)
 * | _out_ `stepNumber`    | The current step starting with 1, ending with _numberOfSteps_+1
 * | _out_ `numberOfSteps` | Total number of steps
 * | _out_ `displayText`   | The display text for the current step
 * | __Response Actions__  | |
 * | `continue`            | The process is not finished and `continue` should be called as next action
 * | `download`            | The process is finished and the next call to `continue` will trigger the file download
 * | `done`                | The process is finished
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ continue </div>
 * <div>
 * Continue to process the next action.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _out_ `stepNumber`    | The current step starting with 1, ending with _numberOfSteps_+1
 * | _out_ `numberOfSteps` | Total number of steps
 * | _out_ `displayText`   | The display text for the current step
 * | __Response Actions__  | |
 * | `continue`            | The process is not finished and `continue` should be called as next action
 * | `download`            | The process is finished and the next call to `continue` will trigger the file download
 * | `done`                | The process is finished
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BatchController extends Controller {

  // session name constants
  const ONE_CALL_SESSION_VARNAME = 'BatchController.oneCall';
  const STEP_SESSION_VARNAME = 'BatchController.curStep';
  const NUM_STEPS_VARNAME = 'BatchController.numSteps';
  const DOWNLOAD_STEP = 'BatchController.downloadStep'; // signals that the next continue action triggers the download
  const WORK_PACKAGES_VARNAME = 'BatchController.workPackages';

  private $_curStep = 1;
  private $_workPackages = array();

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    parent::initialize($request, $response);

    $session = ObjectFactory::getInstance('session');
    if ($request->getAction() == 'continue') {
      // get step for current call from session
      if ($session->exist(self::STEP_SESSION_VARNAME)) {
        $this->_curStep = $session->get(self::STEP_SESSION_VARNAME);
      }
      else {
        throw new ApplicationException($request, $response, ApplicationError::getGeneral("Current step undefined."));
      }
      // get workpackage definition for current call from session
      if ($session->exist(self::WORK_PACKAGES_VARNAME)) {
        $this->_workPackages = $session->get(self::WORK_PACKAGES_VARNAME);
      }
      else {
        throw new ApplicationException($request, $response, ApplicationError::getGeneral("Work packages undefined."));
      }
    }
    else {
      // first call, initialize step session variable
      $this->_curStep = 1;
      $session->set(self::ONE_CALL_SESSION_VARNAME, $request->getBooleanValue('oneCall', false));

      $tmpArray = array();
      $session->set(self::WORK_PACKAGES_VARNAME, $tmpArray);
      $session->set(self::DOWNLOAD_STEP, false);

      // define work packages
      $number = 0;
      while (($workPackage = $this->getWorkPackage($number)) !== null) {
        if (!isset($workPackage['name']) || !isset($workPackage['size']) ||
          !isset($workPackage['oids']) || !isset($workPackage['callback'])) {
          throw new ApplicationException($request, $response, ApplicationError::getGeneral("Incomplete work package description."));
        }
        else {
          $args = isset($workPackage['args']) ? $workPackage['args'] : null;
          $this->addWorkPackage($workPackage['name'], $workPackage['size'], $workPackage['oids'], $workPackage['callback'], $args);
          $number++;
        }
      }
      if ($number == 0) {
        throw new ApplicationException($request, $response, ApplicationError::getGeneral("No work packages."));
      }
    }
    $nextStep = $this->_curStep+1;
    $session->set(self::STEP_SESSION_VARNAME, $nextStep);
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $session = ObjectFactory::getInstance('session');
    $response = $this->getResponse();

    // check if a download was triggered in the last step
    if ($session->get(self::DOWNLOAD_STEP) == true) {
      $file = $this->getDownloadFile();
      header("Content-Type: application/force-download");
      header("Content-Type: application/octet-stream");
      header("Content-Type: application/download");
      header('Content-Disposition: attachment; filename="'.basename($file).'"');
      echo file_get_contents($file);
      exit;
    }

    // continue processing
    $curStep = $this->getStepNumber();
    $numberOfSteps = $this->getNumberOfSteps();
    if ($curStep <= $numberOfSteps) {
      $this->processPart();

      $response->setValue('stepNumber', $curStep);
      $response->setValue('numberOfSteps', $numberOfSteps);
      $response->setValue('displayText', $this->getDisplayText($curStep));
    }

    // check if we are finished or should continue
    // (number of packages may have changed while processing)
    $numberOfSteps = $this->getNumberOfSteps();
    if ($curStep >= $numberOfSteps || $session->get(self::ONE_CALL_SESSION_VARNAME) == true) {
      // finished -> check for download
      $file = $this->getDownloadFile();
      if ($file) {
        $response->setAction('download');
        $session->set(self::DOWNLOAD_STEP, true);
      }
      else {
        $response->setAction('done');
      }
    }
    else {
      // proceed
      $response->setAction('continue');
    }
  }

  /**
   * Get the number of the current step (1..number of steps).
   * @return The number of the current step
   */
  protected function getStepNumber() {
    // since we actally call processPart() in the second step,
    // return the real step number reduced by one
    return $this->_curStep;
  }

  /**
   * Add a work package to session. This package will be devided into sub packages of given size.
   * @param $name Display name of the package (will be supplemented by startNumber-endNumber, e.g. '1-7', '8-14', ...)
   * @param $size Size of one sub package. This defines how many of the oids will be passed to the callback in one call (e.g. '7' means pass 7 oids per call)
   * @param $oids An array of object ids (or other application specific package identifiers) with _at least one value_ that will be distributed into sub packages of given size
   * @param $callback The name of method to call for this package type.
   *      The callback method must accept the following parameters:
   *      1. array parameter (the object ids to process in the current call)
   *      2. optionally array parameter (the additional arguments)
   * @param $args Assoziative array of additional callback arguments (application specific) [default: null]
   */
  protected function addWorkPackage($name, $size, $oids, $callback, $args=null) {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($size < 1) {
      throw new ApplicationException($request, $response,
              ApplicationError::getGeneral("Wrong work package description '".$name."': Size must be at least 1."));
    }
    if (sizeOf($oids) == 0) {
      throw new ApplicationException($request, $response,
              ApplicationError::getGeneral("Wrong work package description '".$name."': No oids given."));
    }
    if (strlen($callback) == 0) {
      throw new ApplicationException($request, $response,
              ApplicationError::getGeneral("Wrong work package description '".$name."': No callback given."));
    }

    $session = ObjectFactory::getInstance('session');
    $workPackages = $session->get(self::WORK_PACKAGES_VARNAME);

    $counter = 1;
    $total = sizeOf($oids);
    while(sizeOf($oids) > 0) {
      $items = array();
      for($i=0; $i<$size; $i++) {
        $nextItem = array_shift($oids);
        if($nextItem !== null) {
          $items[] = $nextItem;
        }
      }

      // define status text
      $start = $counter;
      $end = ($counter+sizeOf($items)-1);
      $stepsText = $counter;
      if ($start != $end) {
        $stepsText .= '-'.($counter+sizeOf($items)-1);
      }
      $statusText = "";
      if ($total > 1) {
        $statusText = $stepsText.'/'.$total;
      }

      $curWorkPackage = array('name' => $name.' '.$statusText,
                         'oids' => $items,
                         'callback' => $callback,
                         'args' => $args);
      $workPackages[] = $curWorkPackage;
      $counter += $size;
    }
    $session->set(self::WORK_PACKAGES_VARNAME, $workPackages);
    $session->set(self::NUM_STEPS_VARNAME, sizeOf($workPackages));

    $this->_workPackages = $workPackages;
  }

  /**
   * Process the next step.
   */
  protected function processPart() {
    $curWorkPackageDef = $this->_workPackages[$this->getStepNumber()-1];
    if (strlen($curWorkPackageDef['callback']) == 0) {
      throw new ApplicationException($request, $response, ApplicationError::getGeneral("Empty callback name."));
    }
    else {
      if (!method_exists($this, $curWorkPackageDef['callback'])) {
        throw new ApplicationException($request, $response,
                ApplicationError::getGeneral("Method '".$curWorkPackageDef['callback']."' must be implemented by ".get_class($this)));
      }
      else {
        call_user_func(array($this, $curWorkPackageDef['callback']), $curWorkPackageDef['oids'], $curWorkPackageDef['args']);
      }
    }
  }

  /**
   * Get the number of steps to process.
   * @return Integer
   */
  protected function getNumberOfSteps() {
    $session = ObjectFactory::getInstance('session');
    return $session->get(self::NUM_STEPS_VARNAME);
  }

  /**
   * Get the text to display for the current step.
   * @param $step The step number
   */
  protected function getDisplayText($step) {
    return Message::get("Processing")." ".$this->_workPackages[$step-1]['name']." ...";
  }

  /**
   * Get the filename of the file to download at the end of processing.
   * @return String of null, if no download is created.
   */
  protected function getDownloadFile() {
    return null;
  }

  /**
   * Get definitions of work packages.
   * @param $number The number of the work package (first number is 0, number is incremented on every call)
   * @note This function gets called on first initialization run as often until it returns null.
   * This allows to define different static work packages. If you would like to add work packages dynamically on
   * subsequent runs this may be done by directly calling the BatchController::addWorkPackage() method.
   * @return A work packages description as assoziative array with keys 'name', 'size', 'oids', 'callback'
   *         as required for BatchController::addWorkPackage() method or null to terminate.
   */
  protected abstract function getWorkPackage($number);
}
?>
