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
 * FailureController is used to signal a failure to the user.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Display the errors contained in the request.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _out_ `errors`        | Array of error messages
 * | __Response Actions__  | |
 * | `ok`                  | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FailureController extends Controller {

  /**
   * @see Controller::doExecute()
   */
  function doExecute() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $response->setValue('errors', $request->getErrors());
    $response->setContext('');
    $response->setAction('ok');
  }
}
?>
