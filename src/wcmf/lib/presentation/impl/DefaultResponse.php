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
namespace wcmf\lib\presentation\impl;

use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\impl\AbstractControllerMessage;

/**
 * Default Response implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultResponse extends AbstractControllerMessage implements Response {

  private $_cacheId = null;
  private $_status = self::STATUS_200;

  /**
   * @see Response::setCacheId()
   */
  public function setCacheId($cacheId) {
    $this->_cacheId = $cacheId;
  }

  /**
   * @see Response::getCacheId()
   */
  public function getCacheId() {
    return $this->_cacheId;
  }

  /**
   * @see Response::setStatus()
   */
  public function setStatus($status) {
    $this->_status = $status;
  }

  /**
   * @see Response::getStatus()
   */
  public function getStatus() {
    return $this->_status;
  }
}
?>
