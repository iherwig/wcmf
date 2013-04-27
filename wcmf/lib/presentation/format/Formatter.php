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
namespace wcmf\lib\presentation\format;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\io\EncodingUtil;
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
   * @note UTF-8 encoded request data is decoded automatically
   * @param request A reference to the Request instance
   */
  public static function deserialize(Request $request) {

    // decode UTF-8
    $data = $request->getValues();
    foreach ($data as $key => $value) {
      if (is_string($value) && EncodingUtil::isUtf8($value)) {
        $data[$key] = EncodingUtil::convertCp1252Utf8ToIso($value);
      }
    }
    $request->setValues($data);

    // get the formatter that should be used for this request format
    $formatter = $request->getFormat();
    if ($formatter == null) {
      // the format must be given!
      throw new ConfigurationException("No content format defined for ".$request->__toString());
    }
    $formatter->deserialize($request);
  }

  /**
   * Serialize Response according to the output format.
   * @param response A reference to the Response instance
   */
  public static function serialize(Response $response) {
    // get the formatter that should be used for this response format
    $formatter = $response->getFormat();
    if ($formatter == null) {
      // the response format must be given!
      throw new ConfigurationException("No response format defined for ".$response->__toString());
    }
    
    header('HTTP/1.1 '.$response->getStatus());
    foreach ($response->getHeaders() as $name => $value) {
      header($name.': '.$value);
    }
    $formatter->serialize($response);
  }
}
?>
