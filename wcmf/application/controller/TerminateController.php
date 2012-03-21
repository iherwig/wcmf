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
 * TerminateController stops the action processing by returning
 * false in executeKernel.
 *
 * The controller passes all input parameters to the output.
 *
 * <b>Input actions:</b>
 * - unspecified: Terminate action processing
 *
 * <b>Output actions:</b>
 * - none
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TerminateController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  public function hasView()
  {
    return false;
  }
  /**
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  public function executeKernel()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $response->setValues($request->getValues());
    return false;
  }
}
?>
