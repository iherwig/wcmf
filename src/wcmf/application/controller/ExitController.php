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
 * ExitController stops the script execution immediatly by calling
 * the exit function.
 *
 * <b>Input actions:</b>
 * - unspecified: Stop script execution
 *
 * <b>Output actions:</b>
 * - none
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ExitController extends Controller {

  /**
   * @see Controller::executeKernel()
   */
  function executeKernel() {
    exit;
  }
}
?>
