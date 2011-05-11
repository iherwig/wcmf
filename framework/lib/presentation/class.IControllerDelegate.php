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

/**
 * @interface IControllerDelegate
 * @ingroup Presentation
 * @brief IControllerDelegate is used to define an interface to vary the behaviour of the
 * Controller base class. The methods are called by the Controller base class on defined variation
 * points and get the currently executed controller as parameter.
 *
 * @note: There is only one instance used for all controllers, which is passed to the controller
 * instances on construction.
 *
 * Users may implement special application requirements by implementing IControllerDelegate and
 * configuring its usage in the configuration section 'implementation', key 'ControllerDelegate'.
 * If no ControllerDelegate is configured, none is used.
 * e.g.
 *
 * @code
 * [implementation]
 * ...
 * ControllerDelegate = MyControllerDelegate
 * ...
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface IControllerDelegate
{
  /**
   * This method is called after the base class initialize method.
   * @see Controller::initialize()
   * @param controller The currently executed controller
   */
  function postInitialize(Controller $controller);
  /**
   * This method is called before the base class validate method.
   * @see Controller::validate()
   * @param controller The currently executed controller
   * @return The validation result.
   */
  function preValidate(Controller $controller);
  /**
   * This method is called after validation and before the base class execute method
   * @see Controller::execute()
   * @param controller The currently executed controller
   */
  function preExecute(Controller $controller);
  /**
   * This method is called after the base class execute method.
   * @see Controller::execute()
   * @param controller A reference to the currently executed controller
   * @param result The result of Controller::executeKernel
   * @return The execution result.
   */
  function postExecute(Controller $controller, $result);
  /**
   * Assign additional variables to the response.
   * This method is called after the base class assignResponseDefaults method.
   * @param controller The currently executed controller
   */
  function assignAdditionalResponseValues(Controller $controller);
}
?>
