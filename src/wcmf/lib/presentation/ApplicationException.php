<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * ApplicationException signals a general application exception.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ApplicationException extends \Exception {

  private $request = null;
  private $response = null;
  private $error = null;

  /**
   * Constructor
   * @param $request The current request
   * @param $response The current response
   * @param $error An ApplicationError instance
   */
  public function __construct(Request $request, Response $response,
    ApplicationError $error) {

    // set status code on response
    $response->setStatus($error->getStatusCode());

    $this->request = $request;
    $this->response = $response;
    $this->error = $error;

    parent::__construct($error->getMessage());
  }

  /**
   * Get the current request
   * @return The Request instance
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the current response
   * @return The Response instance
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Get the error
   * @return The ApplicationError instance
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Get a string representation of the exception
   * @return String
   */
  public function __toString() {
    $str = $this->error->__toString().", ";
    if ($this->request) {
      $str .= "Request: ".$this->request->__toString().", ";
    }
    if ($this->response) {
      $str .= "Response: ".$this->response->__toString().", ";
    }
    $str .= "\n".parent::getTraceAsString();
    return $str;
  }
}
?>
