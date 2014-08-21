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
 * FailureController is used to show an error page to the user.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Display the errors contained in the request.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | __Response Actions__  | |
 * | `failure`             | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FailureController extends Controller {

  /**
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
