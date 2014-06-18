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
