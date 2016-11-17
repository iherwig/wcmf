<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

/**
 * ControllerMethods implements a doExecute() method, that delegates to the
 * method provided in its argument.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
trait ControllerMethods {

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    if (method_exists($this, $method)) {
      call_user_func(array($this, $method));
    }
    else {
      throw new \Exception("The method '".$method."' is not defined in class ".get_class($this));
    }
  }
}
?>
