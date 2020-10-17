<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
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
 * DownloadFormat is used for downloads. It will be automatically chosen, if
 * a file is set using the Response::setFile() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DownloadFormat extends AbstractFormat {

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType(Response $response=null) {
    return 'application/octet-stream';
  }

  /**
   * @see Format::isCached()
   */
  public function isCached(Response $response) {
    $cacheDate = $this->getCacheDate($response);
    if ($cacheDate === null) {
      return false;
    }
    $cacheLifetime = $response->getCacheLifetime();
    $expireDate = null;
    if ($cacheLifetime !== null && $cacheDate !== null) {
      $expireDate = clone $cacheDate;
      $expireDate->modify('+'.$cacheLifetime.' seconds');
    }
    return $expireDate === null || $expireDate < new \DateTime();
  }

  /**
   * @see Format::getCacheDate()
   */
  public function getCacheDate(Response $response) {
    $document = $response->getDocument();
    return $document ? $document->getCacheDate() : null;
  }

  /**
   * @see Format::getResponseHeaders()
   */
  public function getResponseHeaders(Response $response) {
    $document = $response->getDocument();
    if ($document) {
      $response->setHeader("Content-Type", $document->getMimeType());
      if ($document->isDownload()) {
        $response->setHeader("Content-Disposition", 'attachment; filename="'.basename($document->getFilename()).'"');
      }
    }
    return $response->getHeaders();
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
    $document = $response->getDocument();
    if ($document) {
      $document->output();
    }
    return $response->getValues();
  }
}
?>
