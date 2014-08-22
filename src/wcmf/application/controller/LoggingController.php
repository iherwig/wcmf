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

use wcmf\lib\core\Log;
use wcmf\lib\presentation\Controller;

/**
 * LoggingController is used to log a message in the backend log.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Log the message.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `type`            | The type of message. Must be one of: _DEBUG_, _INFO_, _WARNING_, _ERROR_, _FATAL_
 * | _in_ `message`         | The message
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LoggingController extends Controller {

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {

    $request = $this->getRequest();
    $response = $this->getResponse();

    $logType = $request->getValue('type');
    $message = $request->getValue('message');
    switch($logType) {
      case 'TRACE':
        Log::trace($message, __CLASS__);
        break;

      case 'DEBUG':
        Log::debug($message, __CLASS__);
        break;

      case 'INFO':
        Log::info($message, __CLASS__);
        break;

      case 'WARNING':
        Log::warn($message, __CLASS__);
        break;

      case 'ERROR':
        Log::error($message, __CLASS__);
        break;

      case 'FATAL':
        Log::fatal($message, __CLASS__);
        break;

      default:
        Log::error("Unknown log message type: ".$logType, __CLASS__);
    }

    $response->setAction('ok');
  }
}
?>

