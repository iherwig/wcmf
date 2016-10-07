<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\format;

use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * Format defines the interface for all format classes. Format instances
 * are used to map external data representations like JSON, XML/SOAP or HTML
 * to internal ones and vice versa. All data values are supposed to be scalar or
 * array values except for wcmf::lib::model::Node instances, for which each external
 * representation defines a special notation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Format {

  /**
   * Get the MIME type of the format
   * @return String
   */
  public function getMimeType();

  /**
   * Deserialize Request data from the external representation into Nodes and scalars/arrays.
   * @param $request A reference to the Request instance
   */
  public function deserialize(Request $request);

  /**
   * Serialize Response data according to the external representation.
   * @param $response A reference to the Response instance
   */
  public function serialize(Response $response);

  /**
   * Check if the formatted response will be returned from a cache and does not
   * require further processing.
   * @return Boolean
   */
  public function isCached(Response $response);
}
?>
