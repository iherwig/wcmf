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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response; // ambiguous
use wcmf\lib\presentation\format\Format;

/**
 * NullFormat passes through the original request and response objects
 * without modifying or transforming them.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullFormat implements Format {

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'null';
  }

  /**
   * @see Format::deserialize()
   */
  public function deserialize(Request $request) {}

  /**
   * @see Format::serialize()
   */
  public function serialize(Response $response) {}
}
?>
