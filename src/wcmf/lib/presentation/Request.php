<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\format\Format;

/**
 * Request holds the request values that are used as input to
 * Controller instances. It is typically instantiated and filled by the
 * ActionMapper.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Request extends ControllerMessage {

  /**
   * Initialize the request instance from the HTTP request.
   * @param $controller The controller to call if none is given in request parameters (optional)
   * @param $context The context to set if none is given in request parameters (optional)
   * @param $action The action to perform if none is given in request parameters (optional)
   */
  public function initialize($controller=null, $context=null, $action=null);

  /**
   * Get the HTTP method of the request
   * @return String
   */
  public function getMethod();

  /**
   * Set the desired response format
   * @param $format Format instance
   */
  public function setResponseFormat(Format $format);

  /**
   * Set the desired response format
   * @param $name A key of the configuration section 'Formats'
   */
  public function setResponseFormatByName($name);

  /**
   * Get the message response format. If no explicit format is set, the
   * format is derived from the Content-Type header value, if existing.
   * If no format can be derived, the first format in the configuration
   * key 'Formats' will be used.
   * @return Format instance
   */
  public function getResponseFormat();
}
?>
