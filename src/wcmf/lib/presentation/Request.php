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
namespace wcmf\lib\presentation;

/**
 * Request holds the request values that are used as input to Controller instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Request extends ControllerMessage {

  /**
   * Set the Response instance belonging to the request and vice versa.
   * @param $response Response
   */
  public function setResponse(Response $response);

  /**
   * Get the Response instance belonging to the request.
   * @return Response
   */
  public function getResponse();

  /**
   * Initialize the request instance from the HTTP request.
   * @param $controller The controller to call if none is given in request parameters (optional)
   * @param $context The context to set if none is given in request parameters (optional)
   * @param $action The action to perform if none is given in request parameters (optional)
   */
  public function initialize($controller=null, $context=null, $action=null);

  /**
   * Get the HTTP method of the request
   * @return String (uppercase)
   */
  public function getMethod();

  /**
   * Set the desired response format
   * @param $format A key of the configuration section 'Formats'
   */
  public function setResponseFormat($format);

  /**
   * Get the message response format. If no explicit format is set, the
   * format is derived from the Content-Type header value, if existing.
   * If no format can be derived, the first format in the configuration
   * key 'Formats' will be used.
   * @return String
   */
  public function getResponseFormat();
}
?>
