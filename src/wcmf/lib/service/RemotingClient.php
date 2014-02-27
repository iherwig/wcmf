<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\service;

use wcmf\lib\presentation\Request;

/**
 * RemotingClient defines the interface for clients to be used
 * with RemotingFacade.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface RemotingClient {

  /**
   * Do a call to the remote server.
   * @param request A Request instance
   * @return A Response instance
   */
  public function call(Request $request);
}
?>
