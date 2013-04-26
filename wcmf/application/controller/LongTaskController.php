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

use wcmf\lib\presentation\Controller;

/**
 * LongTaskController is a controller that may be used as
 * base class for tasks, that require a long execution time
 * such as construction of a list of pages.
 *
 * This is accomplished by breaking up the task into n pieces
 * (steps: 1..n) and calling this controller recurringly until
 * all n pieces are finished.
 * The pieces are processed by the processPart() method that
 * must be implemented by subclasses. Information about the
 * the progress is provided by the methods getNumberOfSteps()
 * and getStepNumber().
 *
 * To do the recurring calls without user interaction the controller needs
 * a view that calls the submitAction('continue') function in the onLoad()
 * event of the HTML page. If the parameter oneCall is set to true, the controller
 * tries to accomplish the task in one call.
 *
 * A possible configuration could be:
 *
 * @code
 * [actionmapping]
 * ??longTask                     = MyLongTaskController
 * MyLongTaskController??continue = MyLongTaskController
 * MyLongTaskController??done     = DisplayController
 *
 * [views]
 * MyLongTaskController??         = progressbar.tpl
 * @endcode
 *
 * <b>Input actions:</b>
 * - @em continue Continue with the next step
 * - unspecified: Initialize the task
 *
 * <b>Output actions:</b>
 * - @em done If finished
 *
 * @param[in] oneCall True/False wether to accomplish the task in one call (optional, default: false)
 * @param[out] stepNumber The current step starting with 1, ending with numberOfSteps+1
 * @param[out] numberOfSteps Total number of steps
 * @param[out] stepsArray An arry of dots which size is equal to stepNumber (useful for views to iterate over)
 * @param[out] displayText The display text for the current step
 * @param[out] summaryText The summary text (only available in the last step)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class LongTaskController extends Controller {

  // constants
  const STEP_SESSION_VARNAME = 'LongTaskController.curStep';
  const ONE_CALL_SESSION_VARNAME = 'LongTaskController.oneCall';

  // current step
  private $_curStep = 1;

  /**
   * @see Controller::initialize()
   */
  protected function initialize($request, $response) {
    parent::initialize($request, $response);

    $session = ObjectFactory::getInstance('session');
    if ($request->getAction() == 'continue') {
      // get step for current call from session
      if ($session->exist(self::STEP_SESSION_VARNAME)) {
        $this->_curStep = $session->get(self::STEP_SESSION_VARNAME);
      }
      else {
        throw new RuntimeException("Error initializing LongTaskController: ".get_class($this));
      }
    }
    else {
      // first call, initialize step session variable
      $this->_curStep = 1;
      $this->initializeTask();
      $session->set(self::ONE_CALL_SESSION_VARNAME, $request->getBooleanValue('oneCall', false));
    }
    $step = $this->_curStep+1;
    $session->set(self::STEP_SESSION_VARNAME, $step);
  }

  /**
   * Do processing and assign Node data to View.
   * @return Array of given context and action 'done' if finished.
   *         False else.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    // call processPart() in the second step,
    // in the first step show status only
    if ($this->_curStep > 1) {
      $this->processPart();
    }
    if ($this->_curStep <= $this->getNumberOfSteps()+1) {
      $this->_response->setValue('stepNumber', $this->_curStep);
      $this->_response->setValue('numberOfSteps', $this->getNumberOfSteps());
      // assign an array holding number of steps elements for use with
      // smarty section command
      $stepsArray = array();
      for ($i=0; $i<$this->getNumberOfSteps(); $i++) {
        $stepsArray[] = '.';
      }
      $this->_response->setValue('stepsArray', $stepsArray);
      $this->_response->setValue('displayText', $this->getDisplayText($this->_curStep));

      // add the summary message
      $this->_response->setValue('summaryText', $this->getSummaryText());

      $session = ObjectFactory::getInstance('session');
      if ($session->get(self::ONE_CALL_SESSION_VARNAME) == false) {
        // show progress bar
        return false;
      }
      else {
        // proceed
        $this->_response->setAction('continue');
        return true;
      }
    }
    else {
      // return control to application
      $this->_response->setAction('done');
      return true;
    }
  }

  /**
   * Get the number of the current step (1..number of steps).
   * @return The number of the current step
   */
  protected function getStepNumber() {
    // since we actally call processPart() in the second step,
    // return the real step number reduced by one
    return $this->_curStep-1;
  }

  /**
   * Get the total number of steps.
   * @return The total number of steps
   */
  protected abstract function getNumberOfSteps();

  /**
   * Get the display text for a step.
   * @param step The step to get the text for.
   * @return The display text
   */
  protected abstract function getDisplayText($step);

  /**
   * Get the summary text for the last step.
   * @return The summary text
   */
  protected abstract function getSummaryText();

  /**
   * Initialize the task e.g. store some configuration in the session.
   * This method is called on start up.
   * @note subclasses override this method to implement special application requirements.
   */
  protected function initializeTask() {}

  /**
   * Process one part of the task.
   */
  protected abstract function processPart();
}
?>
