<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\application\controller;

use wcmf\lib\presentation\Controller;

/**
 * FailureController is a controller that shows an error page to the user.
 *
 * <b>Input actions:</b>
 * - unspecified: Display the errors contained in the request
 *
 * <b>Output actions:</b>
 * - @em failure In any case
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FailureController extends Controller {

  /**
   * Assign error message to Response.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $response->setErrors($request->getErrors());
    $response->setAction('failure');
    return false;
  }
}
?>
