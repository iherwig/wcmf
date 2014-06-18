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
 * LoggingController is a controller that logs a message.
 *
 * <b>Input actions:</b>
 * - unspecified: Log the message
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] type The type of message. Must be one of: DEBUG, INFO, WARNING, ERROR, FATAL
 * @param[in] message The message
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LoggingController extends Controller {

  /**
   * Log the message.
   * @return True in any case
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {

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
    return true;
  }
}
?>

