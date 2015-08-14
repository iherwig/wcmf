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
namespace wcmf\lib\service;

use wcmf\lib\presentation\Request;

/**
 * RemotingClient defines the interface for clients to be used
 * with RemotingServer.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface RemotingClient {

  /**
   * Do a call to the remote server.
   * @param $request A Request instance
   * @return A Response instance
   */
  public function call(Request $request);
}
?>
