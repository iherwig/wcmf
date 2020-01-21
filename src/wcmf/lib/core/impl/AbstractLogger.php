<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\core\impl;

use wcmf\lib\core\Logger;

/**
 * AbstractLogger is the abstract base class for Logger implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractLogger implements Logger {

  /**
   * @see Logger::logByErrorType()
   */
  public function logByErrorType($type, $message) {
    switch ($type) {
      case E_NOTICE:
      case E_USER_NOTICE:
      case E_STRICT:
      case E_DEPRECATED:
      case E_USER_DEPRECATED:
        $this->info($message);
        break;
      case E_WARNING:
      case E_USER_WARNING:
        $this->warn($message);
        break;
      default:
        $this->error($message);
    }
  }
}
?>
