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
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\Request;

/**
 * ActionMapper implementations are responsible for instantiating and
 * executing Controllers based on the referring Controller and the given action.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ActionMapper {

  /**
   * Process an action depending on a given referrer. The ActionMapper will instantiate the required Controller class
   * as determined by the request's action key and delegates the request to it.
   * @note This method is static so that it can be used without an instance. (This is necessary to call it in onError() which
   * cannot be a class method because php's set_error_handler() does not allow this).
   * @param $request A reference to a Request instance
   * @return Response instance
   */
  public function processAction(Request $request);

  /**
   * Reset the state of ActionMapper to initial. Especially clears the processed controller queue.
   */
  public function reset();
}
?>
