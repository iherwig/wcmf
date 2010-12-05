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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");

/**
 * @class FailureController
 * @ingroup Controller
 * @brief FailureController is a controller that shows an error page to the user.
 * 
 * <b>Input actions:</b>
 * - unspecified: Display the error message
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 * 
 * @param[in,out] errorMsg The message to display
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FailureController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return true;
  }
  /**
   * Assign error message to View.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    // assign model to view
    $this->_response->setValue('errorMsg', $this->_request->getValue('errorMsg'));
    
    $this->_response->setAction('ok');
    return false;
  }
}
?>
