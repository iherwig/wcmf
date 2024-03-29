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
namespace wcmf\lib\persistence\output\impl;

use wcmf\lib\core\LogTrait;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PersistentObject;

/**
 * DefaultOutputStrategy outputs an object's content to the Log category DefaultOutputStrategy.
 * Classes used must implement the toString() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultOutputStrategy implements OutputStrategy {
  use LogTrait;

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    self::logger()->info("DOCUMENT START.");
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    self::logger()->info("DOCUMENT END.");
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    if (self::logger()->isInfoEnabled()) {
      self::logger()->info($obj->dump());
    }
  }
}
?>
