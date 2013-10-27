<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\Request;

/**
 * ActionMapper implementations are responsible for instatiating and
 * executing Controllers based on the referring Controller and the given action.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
interface ActionMapper {

  /**
   * Process an action depending on a given referrer. The ActionMapper will instantiate the required Controller class
   * as configured in the iniFile and delegates the request to it.
   * @note This method is static so that it can be used without an instance. (This is necessary to call it in onError() which
   * cannot be a class method because php's set_error_handler() does not allow this).
   * @param request A reference to a Request instance
   * @return A reference to an Response instance or null on error.
   */
  public function processAction(Request $request);

  /**
   * Reset the state of ActionMapper to initial. Especially clears the processed controller queue.
   */
  public function reset();
}
?>
