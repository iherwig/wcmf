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
namespace wcmf\application\controller;

use wcmf\application\controller\LongTaskController;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Message;
use wcmf\lib\presentation\Controller;

/**
 * BatchController allows to define work packages that will be processed
 * in a sequence. It simplifies the usage of LongTaskController functionality
 * for splitting different bigger tasks into many smaller (similar) tasks where
 * the whole number of tasks isn't known at designtime.
 *
 * <b>Input actions:</b>
 * - @em continue Process next work package if any
 * - unspecified: Initialized work packages
 *
 * <b>Output actions:</b>
 * - see LongTaskController
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BatchController extends LongTaskController {

  // session name constants
  private $WORK_PACKAGES_VARNAME = 'BatchController.workPackages';
  private $NUM_STEPS_VARNAME = 'BatchController.numSteps';

  private $_workPackages = array();

  /**
   * @see Controller::initialize()
   */
  protected function initialize($request, $response) {
    parent::initialize($request, $response);

    // define work packages
    $session = Session::getInstance();
    if ($request->getAction() == 'continue') {
      // get export definition for current call from session
      if ($session->exist($this->WORK_PACKAGES_VARNAME)) {
        $this->_workPackages = $session->get($this->WORK_PACKAGES_VARNAME);
      }
      else {
        throw new RuntimeException("Error initializing BatchController: ".get_class($this));
      }
    }
    else {
      // first call, initialize session variable
      $tmpArray = array();
      $session->set($this->WORK_PACKAGES_VARNAME, $tmpArray);

      // define work packages
      $number = 0;
      while (($workPackage = $this->getWorkPackage($number)) !== null) {
        if (!isset($workPackage['name']) || !isset($workPackage['size']) ||
          !isset($workPackage['oids']) || !isset($workPackage['callback'])) {
          throw new RuntimeException("Incomplete work package description.");
        }
        else {
          $this->addWorkPackage($workPackage['name'], $workPackage['size'], $workPackage['oids'], $workPackage['callback'], $workPackage['args']);
          $number++;
        }
      }
      if ($number == 0) {
        throw new RuntimeException("Error initializing BatchController: ".get_class($this));
      }
    }
  }

  /**
   * Add a work package to session. This package will be devided into sub packages of given size.
   * @param name Display name of the package (will be supplemented by startNumber-endNumber, e.g. '1-7', '8-14', ...)
   * @param size Size of one sub package. This defines how many of the oids will be passed to the callback in one call (e.g. '7' means pass 7 oids per call)
   * @param oids An array of oids (or other application specific package identifiers) that will be distributed into sub packages of given size
   * @note The array must contain at least one value
   * @param callback The name of method to call for this package type
   * @note The callback method must accept the following parameters:
   *      - one array parameter (the oids to process in the current call)
   *      - optionally array parameter (the additional arguments)
   * @param args Assoziative array of additional callback arguments (application specific) [default: null]
   */
  protected function addWorkPackage($name, $size, $oids, $callback, $args=null) {
    if ($size < 1) {
      throw new RuntimeException("Wrong work package description '".$name."': Size must be at least 1.");
    }
    if (sizeOf($oids) == 0) {
      throw new RuntimeException("Wrong work package description '".$name."': No oids given.");
    }
    if (strlen($callback) == 0) {
      throw new RuntimeException("Wrong work package description '".$name."': No callback given.");
    }

    $session = Session::getInstance();
    $workPackages = $session->get($this->WORK_PACKAGES_VARNAME);

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
      array_push($workPackages, $curWorkPackage);
      $counter += $size;
    }
    $session->set($this->WORK_PACKAGES_VARNAME, $workPackages);
    $session->set($this->NUM_STEPS_VARNAME, sizeOf($workPackages));

    $this->_workPackages = $workPackages;
  }

  /**
   * Get definitions of work packages.
   * @param number The number of the work package (first number is 0, number is incremented on every call)
   * @note This function gets called on first initialization run as often until it returns null.
   * This allows to define different static work packages. If you would like to add work packages dynamically on
   * subsequent runs this may be done by directly calling the BatchController::addWorkPackage() method.
   * @return A work packages description as assoziative array with keys 'name', 'size', 'oids', 'callback'
   *         as required for BatchController::addWorkPackage() method or null to terminate.
   */
  protected abstract function getWorkPackage($number);

  /**
   * @see LongTaskController::getNumberOfSteps()
   */
  protected function getNumberOfSteps() {
    $session = Session::getInstance();
    return $session->get($this->NUM_STEPS_VARNAME);
  }

  /**
   * @see LongTaskController::getDisplayText()
   */
  protected function getDisplayText($step) {
    return Message::get("Processing")." ".$this->_workPackages[$step-1]['name']." ...";
  }

  /**
   * @see LongTaskController::getSummaryText()
   * The default implementation returns an empty string
   */
  protected function getSummaryText() {
    return "";
  }

  /**
   * @see LongTaskController::processPart()
   */
  protected function processPart() {
    if ($this->getStepNumber()-1 == $this->getNumberOfSteps()) {
      return;
    }

    $curWorkPackageDef = $this->_workPackages[$this->getStepNumber()-1];
    if (strlen($curWorkPackageDef['callback']) == 0) {
      throw new RuntimeException("Empty callback name.");
    }
    else {
      if (!method_exists($this, $curWorkPackageDef['callback'])) {
        throw new RuntimeException("Method '".$curWorkPackageDef['callback']."' must be implemented by ".get_class($this));
      }
      else {
        call_user_method($curWorkPackageDef['callback'], &$this, $curWorkPackageDef['oids'], $curWorkPackageDef['args']);
      }
    }
  }
}
?>
