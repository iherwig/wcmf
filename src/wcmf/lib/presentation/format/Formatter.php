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
namespace wcmf\lib\presentation\format;

use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * Formatter is the single entry point for request/response formatting.
 * It chooses the configured formatter based on the format property of the request
 * by getting the value XXXFormat from the configuration section 'formats'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Formatter {

  /**
   * Get a Format instance from it's name.
   * @param string $name The format name
   * @return Format
   */
  public function getFormat(string $name): Format;

  /**
   * Get the format name for the given mime type.
   * @param ?string $mimeType The mime type
   * @return ?string
   */
  public function getFormatFromMimeType(?string $mimeType): ?string;

  /**
   * Deserialize Request data into objects.
   * @param Request $request The Request instance
   */
  public function deserialize(Request $request): void;

  /**
   * Serialize Response according to the output format.
   * @param Response $response The Response instance
   */
  public function serialize(Response $response): void;
}
?>
