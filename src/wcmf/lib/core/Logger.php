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
namespace wcmf\lib\core;

use Psr\Log\LoggerInterface;

/**
 * Interface for logger implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Logger extends LoggerInterface {

  /**
   * Checks whether the info log level is enabled
   * @return bool
   */
  public function isInfoEnabled(): bool;

  /**
   * Checks whether the debug log level is enabled
   * @return bool
   */
  public function isDebugEnabled(): bool;

  /**
   * Log the given message and choose the level from
   * the given type
   * @param int $type PHP error constant (e.g. E_WARNING)
   * @param string $message
   */
  public function logByErrorType(int $type, string $message): void;

  /**
   * Get a Logger instance by name
   * @param string $name The name
   * @return Logger
   */
  public static function getLogger(string $name): Logger;
}
?>
