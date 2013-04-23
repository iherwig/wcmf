<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\ControllerMessage;
use wcmf\lib\presentation\format\Format;

/**
 * Request holds the request values that are used as input to
 * Controller instances. It is typically instantiated and filled by the
 * ActionMapper.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Request extends ControllerMessage {

  private $_responseFormat = null;

  /**
   * Set the desired response format
   * @param format Format instance
   */
  public function setResponseFormat(Format $format) {
    $this->_responseFormat = $format;
  }

  /**
   * Get the message response format. If no explicit format is set, the
   * format is derived from the Content-Type header value, if existing.
   * If no format can be derived, the first format in the configuration
   * key 'Formats' will be used.
   * @return Format instance
   */
  public function getResponseFormat() {
    if ($this->_responseFormat == null) {
      $this->_responseFormat = self::getFormatFromMimeType($this->getHeader('Accept'));
    }
    return $this->_responseFormat;
  }
}
?>
