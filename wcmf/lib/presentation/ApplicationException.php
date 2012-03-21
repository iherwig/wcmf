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

use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * @class ApplicationException
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ApplicationException extends \Exception {

  private $_request = null;
  private $_response = null;
  private $_error = null;

  /**
   * Constructor
   * @param request The current request
   * @param response The current response
   * @param error An ApplicationError instance
   */
  public function __construct(Request $request, Response $response,
    ApplicationError $error) {

    $this->_request = $request;
    $this->_response = $response;
    $this->_error = $error;

    parent::__construct($error->getMessage());
  }

  /**
   * Get the current request
   * @return The Request instance
   */
  public function getRequest() {
    return $this->_request;
  }

  /**
   * Get the current response
   * @return The Response instance
   */
  public function getResponse() {
    return $this->_response;
  }

  /**
   * Get the error
   * @return The ApplicationError instance
   */
  public function getError() {
    return $this->_error;
  }
}
?>
