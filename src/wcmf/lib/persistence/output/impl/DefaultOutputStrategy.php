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
namespace wcmf\lib\persistence\output\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\output\OutputStrategy;

/**
 * DefaultOutputStrategy outputs an object's content to the Log category DefaultOutputStrategy.
 * Classes used must implement the toString() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultOutputStrategy implements OutputStrategy {

  private static $_logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$_logger == null) {
      self::$_logger = ObjectFactory::getInstance('logManager')->getLogger(__CLASS__);
    }
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    if (self::$_logger->isInfoEnabled()) {
      self::$_logger->info("DOCUMENT START.");
    }
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    if (self::$_logger->isInfoEnabled()) {
      self::$_logger->info("DOCUMENT END.");
    }
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    if (self::$_logger->isInfoEnabled()) {
      self::$_logger->info($obj->toString());
    }
  }
}
?>
