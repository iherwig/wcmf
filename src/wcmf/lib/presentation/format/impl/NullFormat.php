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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\format\Format;

/**
 * NullFormat transfers the original request and response objects
 * without modifying or transforming them.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullFormat extends AbstractFormat {

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'null';
  }

  /**
   * @see Format::isCached()
   */
  public function isCached(Response $response) {
    return false;
  }

  /**
   * @see Format::isCached()
   */
  public function getCacheDate(Response $response) {
    return null;
  }

  /**
   * @see AbstractFormat::deserializeValues()
   */
  protected function deserializeValues(Request $request) {
    return $request->getValues();
  }

  /**
   * @see AbstractFormat::serializeValues()
   */
  protected function serializeValues(Response $response) {
    return $response->getValues();
  }
}
?>
