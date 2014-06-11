<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\ControllerMessage;

/**
 * Response holds the response values that are used as output from
 * Controller instances. It is typically instantiated by the ActionMapper
 * instance and filled during Controller execution.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Response extends ControllerMessage {

  const STATUS_200 = '200 OK';
  const STATUS_201 = '201 Created';
  const STATUS_202 = '202 Accepted';
  const STATUS_204 = '204 No Content';
  
  const STATUS_400 = '400 Bad Request';
  const STATUS_404 = '404 Not Found';

  private $_cacheId = null;
  private $_status = self::STATUS_200;

  /**
   * Set a string value that uniquely identifies the request data
   * that cause the current response. This value maybe used to compare
   * two requests and return cached responses based on the result.
   * @param cacheId
   */
  public function setCacheId($cacheId) {
    $this->_cacheId = $cacheId;
  }

  /**
   * Get the cache id.
   * @see Response::setCacheId()
   * @return The id
   */
  public function getCacheId() {
    return $this->_cacheId;
  }

  /**
   * Set the response HTTP status code
   * @param status The HTTP status code
   */
  public function setStatus($status) {
    $this->_status = $status;
  }

  /**
   * Get the response HTTP status code
   * @return String
   */
  public function getStatus() {
    return $this->_status;
  }
}
?>
