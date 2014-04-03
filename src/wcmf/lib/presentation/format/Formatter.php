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
namespace wcmf\lib\presentation\format;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * Formatter is is the single entry point for request/response formatting.
 * It chooses the configured formatter based on the format property of the message
 * by getting the value XXXFormat from the configuration section 'formats'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Formatter {

  /**
   * Deserialize Request data into objects.
   * @param request A reference to the Request instance
   */
  public static function deserialize(Request $request) {

    // get the formatter that should be used for this request format
    $format = $request->getFormat();
    if ($format == null) {
      // the format must be given!
      throw new ConfigurationException("No content format defined for ".$request->__toString());
    }
    $format->deserialize($request);
  }

  /**
   * Serialize Response according to the output format.
   * @param response A reference to the Response instance
   */
  public static function serialize(Response $response) {
    // get the formatter that should be used for this response format
    $format = $response->getFormat();
    if ($format == null) {
      // the response format must be given!
      throw new ConfigurationException("No response format defined for ".$response->__toString());
    }

    if (!headers_sent()) {
      header('HTTP/1.1 '.$response->getStatus());
      header("Content-Type: ".$format->getMimeType()."; charset=utf-8");
      foreach ($response->getHeaders() as $name => $value) {
        header($name.': '.$value);
      }
    }
    $format->serialize($response);
  }
}
?>
