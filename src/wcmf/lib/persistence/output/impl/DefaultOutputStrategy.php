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

use wcmf\lib\core\Log;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\output\OutputStrategy;

/**
 * DefaultOutputStrategy outputs an object's content to the Log category DefaultOutputStrategy.
 * Classes used must implement the toString() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultOutputStrategy implements OutputStrategy {

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    Log::info("DOCUMENT START.", __CLASS__);
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    Log::info("DOCUMENT END.", __CLASS__);
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    Log::info($obj->toString(), __CLASS__);
  }
}
?>
