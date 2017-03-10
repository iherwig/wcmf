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

use wcmf\lib\presentation\format\Format;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * GenericFormat is used to output arbitrary responses. It prints the
 * content of the 'body' value of the response.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class GenericFormat extends AbstractFormat {

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return '';
  }

  /**
   * @see Format::isCached()
   */
  public function isCached(Response $response) {
    return false;
  }

  /**
   * @see Format::getCacheDate()
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
    echo $response->getValue("body");
    return $response->getValues();
  }
}
?>
