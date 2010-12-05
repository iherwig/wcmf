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
require_once(WCMF_BASE."wcmf/lib/util/class.ObjectFactory.php");
require_once(WCMF_BASE."wcmf/lib/util/class.EncodingUtil.php");

/**
 * @class Formatter
 * @ingroup Format
 * @brief Formatter is is the single entry point for request/response formatting.
 * It chooses the configured formatter based on the format property of the message
 * by getting the value XXXFormat from the configuration section 'implementation'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Formatter
{
  /**
   * Deserialize Request data into objects.
   * @note UTF-8 encoded request data is decoded automatically
   * @param request A reference to the Request instance
   */
  public function deserialize(Request $request)
  {
    // get the formatter that should be used for this request format
    $format = $request->getFormat();
    if ($format == null || strlen($format) == 0)
    {
      // default to html (POST/GET variables) format
      $format = MSG_FORMAT_HTML;
    }
    $formatter = ObjectFactory::createInstanceFromConfig('implementation', $format.'Format');
    if ($formatter === null) {
      throw new ConfigurationException($objectFactory->getErrorMsg()."\nRequest: ".$request->toString());
    }
    // decode UTF-8
    $data = $request->getData();
    foreach ($data as $key => $value)
    {
      if (is_string($value) && EncodingUtil::isUtf8($value)) {
        $data[$key] = EncodingUtil::convertCp1252Utf8ToIso($value);
      }
    }
    $request->setData($data);

    $formatter->deserialize($request);
  }
  /**
   * Serialize Response according to the output format.
   * @param response A reference to the Response instance
   */
  public function serialize(Response $response)
  {
    // get the formatter that should be used for this response format
    $format = $response->getFormat();
    if ($format == null)
    {
      // the response format must be given!
      throw new ConfigurationException("No response format defined for ".$response->toString());
    }
    $formatter = ObjectFactory::createInstanceFromConfig('implementation', $format.'Format');
    if ($formatter === null) {
      throw new ConfigurationException($objectFactory->getErrorMsg()."\nResponse: ".$response->toString());
    }
    $formatter->serialize($response);
  }
}
?>
