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
namespace wcmf\lib\core;

/**
 * A session that requires clients to send a token for authentication.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface TokenBasedSession extends Session {

  /**
   * Get the name of the auth token header.
   * @return String
   */
  public function getHeaderName();

  /**
   * Get the name of the auth token cookie.
   * @return String
   */
  public function getCookieName();
}