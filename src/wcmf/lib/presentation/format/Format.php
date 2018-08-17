<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
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
   * @param $response (optional)
   * @return String
   */
  public function getMimeType(Response $response=null);

  /**
   * Deserialize Request data from the external representation into Nodes and scalars/arrays.
   * @param $request The Request instance
   */
  public function deserialize(Request $request);

  /**
   * Serialize Response data according to the external representation.
   * @param $response The Response instance
   */
  public function serialize(Response $response);

  /**
   * Check if the response identified by it's cache id is cached for this format.
   * @param $response The Response instance
   * @return Boolean
   */
  public function isCached(Response $response);

  /**
   * Get the caching date, if the response is cached.
   * @param $response The Response instance
   * @return DateTime or null, if not cached
   */
  public function getCacheDate(Response $response);

  /**
   * Get the response headers.
   * @param $response The Response instance
   * @return Associative array with header names and values
   */
  public function getResponseHeaders(Response $response);
}
?>
