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
namespace wcmf\lib\core;

/**
 * LogManager is used to retrieve Logger instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LogManager {

  private $_logger = null;

  /**
   * Constructor
   * @param $logger Logger instance
   */
  public function __construct(Logger $logger) {
    $this->_logger = $logger;
  }

  /**
   * Get the logger with the given name
   * @param $name The logger name
   * @return Logger
   */
  public function getLogger($name) {
    return $this->_logger->getLogger($name);
  }
}
?>
