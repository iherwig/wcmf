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
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Stop the script execution.
 * </div>
 * </div>
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
