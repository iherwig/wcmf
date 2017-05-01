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
namespace wcmf\application\controller;

use wcmf\lib\persistence\ObjectId;
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
 * Conceptually the process is divided into sub actions (_work packages_),
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
 * | _out_ `status`        | The value of the response action
 * | __Response Actions__  | |
 * | `progress`            | The process is not finished and `continue` should be called as next action
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
 * | _out_ `displayText`   | The display text for the next step (since the first request)
 * | _out_ `status`        | The value of the response action
 * | __Response Actions__  | |
 * | `progress`            | The process is not finished and `continue` should be called as next action
 * | `download`            | The process is finished and the next call to `continue` will trigger the file download
 * | `done`                | The process is finished
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BatchController extends Controller {

  // session name constants
  const REQUEST_VAR = 'request';
  const ONE_CALL_VAR = 'oneCall';
  const STEP_VAR = 'step';
  const NUM_STEPS_VAR = 'numSteps';
  const DOWNLOAD_STEP_VAR = 'downloadStep'; // signals that the next continue action triggers the download
  const PACKAGES_VAR = 'packages';

  private $curStep = null;
  private $workPackages = [];

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    parent::initialize($request, $response);

    if ($request->getAction() == 'continue') {
      // get step for current call from session
      $this->curStep = $this->getLocalSessionValue(self::STEP_VAR);
      if ($this->curStep === null) {
        throw new ApplicationException($request, $response, ApplicationError::getGeneral("Current step undefined."));
      }
      // get workpackage definition for current call from session
      $this->workPackages = $this->getLocalSessionValue(self::PACKAGES_VAR);
      if ($this->workPackages === null) {
        throw new ApplicationException($request, $response, ApplicationError::getGeneral("Work packages undefined."));
      }
    }
    else {
      // initialize session variables
      $this->setLocalSessionValue(self::ONE_CALL_VAR, $request->getBooleanValue('oneCall', false));
      $this->setLocalSessionValue(self::REQUEST_VAR, $request->getValues());
      $this->setLocalSessionValue(self::PACKAGES_VAR, []);
      $this->setLocalSessionValue(self::STEP_VAR, 0);
      $this->setLocalSessionValue(self::NUM_STEPS_VAR, 0);
      $this->setLocalSessionValue(self::DOWNLOAD_STEP_VAR, false);

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

    // next step
    $this->curStep = $this->getLocalSessionValue(self::STEP_VAR);
    $this->setLocalSessionValue(self::STEP_VAR, ($this->curStep === null ? 0 : $this->curStep+1));
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $response = $this->getResponse();

    // check if a download was triggered in the last step
    if ($this->getLocalSessionValue(self::DOWNLOAD_STEP_VAR) == true) {
      $file = $this->getDownloadFile();
      $response->setFile($file, true);
      $response->setAction('done');
      $this->cleanup();
    }
    else {
      // continue processing
      $oneStep = $this->getLocalSessionValue(self::ONE_CALL_VAR);

      $curStep = $oneStep ? 1 : $this->getStepNumber();
      $numberOfSteps = $this->getNumberOfSteps();
      if ($curStep <= $numberOfSteps) {
        // step 0 only returns the process information
        if ($curStep > 0 || $oneStep) {
          $this->processPart($curStep);
        }

        // update local variables after processing
        $numberOfSteps = $this->getNumberOfSteps();

        // set response data
        $response->setValue('stepNumber', $curStep);
        $response->setValue('numberOfSteps', $numberOfSteps);
        $response->setValue('displayText', $this->getDisplayText($curStep));
      }

      // check if we are finished or should continue
      if ($curStep >= $numberOfSteps || $oneStep) {
        // finished -> check for download
        $file = $this->getDownloadFile();
        if ($file) {
          $response->setAction('download');
          $this->setLocalSessionValue(self::DOWNLOAD_STEP_VAR, true);
        }
        else {
          $response->setAction('done');
          $this->cleanup();
        }
      }
      else {
        // proceed
        $response->setAction('progress');
      }
    }
    $response->setValue('status', $response->getAction());
  }

  /**
   * Get the number of the current step (1..number of steps).
   * @return The number of the current step
   */
  protected function getStepNumber() {
    return $this->curStep;
  }

  /**
   * Add a work package to session. This package will be divided into sub packages of given size.
   * @param $name Display name of the package (will be supplemented by startNumber-endNumber, e.g. '1-7', '8-14', ...)
   * @param $size Size of one sub package. This defines how many of the oids will be passed to the callback in one call (e.g. '7' means pass 7 oids per call)
   * @param $oids An array of object ids (or other application specific package identifiers) that will be distributed into sub packages of given size
   * @param $callback The name of method to call for this package type.
   *      The callback method must accept the following parameters:
   *      1. array parameter (the object ids to process in the current call)
   *      2. optionally array parameter (the additional arguments)
   * @param $args Associative array of additional callback arguments (application specific) (default: _null_)
   */
  protected function addWorkPackage($name, $size, $oids, $callback, $args=null) {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if (strlen($callback) == 0) {
      throw new ApplicationException($request, $response,
              ApplicationError::getGeneral("Wrong work package description '".$name."': No callback given."));
    }

    $workPackages = $this->getLocalSessionValue(self::PACKAGES_VAR);
    $counter = 1;
    $total = sizeof($oids);
    while(sizeof($oids) > 0) {
      $items = [];
      for($i=0; $i<$size && sizeof($oids)>0; $i++) {
        $nextItem = array_shift($oids);
        $items[] = sprintf('%s', $nextItem);
      }

      // define status text
      $start = $counter;
      $end = ($counter+sizeof($items)-1);
      $stepsText = $counter;
      if ($start != $end) {
        $stepsText .= '-'.($counter+sizeof($items)-1);
      }
      $statusText = "";
      if ($total > 1) {
        $statusText = $stepsText.'/'.$total;
      }

      $curWorkPackage = [
        'name' => $name.' '.$statusText,
        'oids' => $items,
        'callback' => $callback,
        'args' => $args
      ];
      $workPackages[] = $curWorkPackage;
      $counter += $size;
    }
    $this->workPackages = $workPackages;

    // update session
    $this->setLocalSessionValue(self::PACKAGES_VAR, $workPackages);
    $this->setLocalSessionValue(self::NUM_STEPS_VAR, sizeof($workPackages));
  }

  /**
   * Process the given step (1-base).
   * @param $step The step to process
   */
  protected function processPart($step) {
    $curWorkPackageDef = $this->workPackages[$step-1];
    $request = $this->getRequest();
    $response = $this->getResponse();
    if (strlen($curWorkPackageDef['callback']) == 0) {
      throw new ApplicationException($request, $response, ApplicationError::getGeneral("Empty callback name."));
    }
    if (!method_exists($this, $curWorkPackageDef['callback'])) {
      throw new ApplicationException($request, $response,
              ApplicationError::getGeneral("Method '".$curWorkPackageDef['callback']."' must be implemented by ".get_class($this)));
    }

    // unserialize oids
    $oids = array_map(function($oidStr) {
      $oid = ObjectId::parse($oidStr);
      return $oid != null ? $oid : $oidStr;
    }, $curWorkPackageDef['oids']);
    call_user_func([$this, $curWorkPackageDef['callback']], $oids, $curWorkPackageDef['args']);
  }

  /**
   * Get a value from the initial request.
   * @param $name The name of the value
   * @return Mixed
   */
  protected function getRequestValue($name) {
    $requestValues = $this->getLocalSessionValue(self::REQUEST_VAR);
    return isset($requestValues[$name]) ? $requestValues[$name] : null;
  }

  /**
   * Get the number of steps to process.
   * @return Integer
   */
  protected function getNumberOfSteps() {
    return $this->getLocalSessionValue(self::NUM_STEPS_VAR);
  }

  /**
   * Get the text to display for the current step.
   * @param $step The step number
   */
  protected function getDisplayText($step) {
    $numPackages = sizeof($this->workPackages);
    return ($step>=0 && $step<$numPackages) ? $this->workPackages[$step]['name']." ..." :
      ($step>=$numPackages ? "Done" : "");
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
   * @return A work packages description as associative array with keys 'name', 'size', 'oids', 'callback'
   *         as required for BatchController::addWorkPackage() method or null to terminate.
   */
  protected abstract function getWorkPackage($number);

  /**
   * Clean up after all tasks are finished.
   * @note Subclasses may override this to do custom clean up, but should call the parent method.
   */
  protected function cleanup() {
    $this->clearLocalSessionValues();
  }
}
?>
