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
class TerminateController extends Controller {

  /**
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $response->setValues($request->getValues());
    return false;
  }
}
?>
