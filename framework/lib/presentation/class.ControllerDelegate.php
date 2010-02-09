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
 * @class ControllerDelegate
 * @ingroup Presentation
 * @brief ControllerDelegate is used to define an interface to vary the behaviour of the 
 * Controller base class. The methods are called by the Controller base class on defined variation 
 * points and get the currently executed controller as parameter.
 * 
 * @note: There is only one instance used for all controllers, which is passed to the controller 
 * instances on construction.
 *
 * Users may implement special application requirements by subclassing ControllerDelegate and
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
class ControllerDelegate
{
  /**
   * This method is called after the base class initialize method.
   * @see Controller::initialize()
   * @param controller The currently executed controller
   */
  function postInitialize(&$controller) 
  {
    WCMFException::throwEx("postInitialize() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * This method is called instead of the base class method.
   * @see Controller::validate()
   * @param controller The currently executed controller
   * @return The validation result.
   */
  function validate(&$controller) 
  { 
    WCMFException::throwEx("validate() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * This method is called after validation and before the base class execute method
   * @see Controller::execute()
   * @param controller The currently executed controller
   */
  function preExecute(&$controller) 
  {
    WCMFException::throwEx("preExecute() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * This method is called after the base class execute method.
   * @see Controller::execute()
   * @param controller A reference to the currently executed controller
   * @param result The result of Controller::executeKernel
   * @return The execution result.
   */
  function postExecute(&$controller, $result) 
  {
    WCMFException::throwEx("postExecute() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Assign additional variables to the view.
   * This method is called after the base class assignViewDefaults method.
   * @param controller The currently executed controller
   */  
  function assignAdditionalViewValues(&$controller) 
  {
    WCMFException::throwEx("assignAdditionalViewValues() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
}
?>
