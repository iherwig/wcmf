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
namespace wcmf\application\controller;

use wcmf\lib\presentation\Controller;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\Cache;
use wcmf\lib\presentation\view\View;

/**
 * AdminController is used to perform admin tasks.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ clearCaches </div>
 * <div>
 * Clear all cache instances.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | __Response Actions__  | |
 * | `ok`                  | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AdminController extends Controller {

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $request = $this->getRequest();
    $response = $this->getResponse();

    // process actions
    if ($request->getAction() == 'clearCaches') {
      $this->clearCaches();
    }

    $response->setAction('ok');
  }

  /**
   * Clear all cache instances found in the configuration
   */
  protected function clearCaches() {
    $config = $this->getConfiguration();
    foreach($config->getSections() as $section) {
      $sectionValues = $config->getSection($section, true);
      if (isset($sectionValues['__class'])) {
        $instance = ObjectFactory::getInstance($section);
        if ($instance instanceof Cache) {
          $instance->clearAll();
        }
        elseif ($instance instanceof View) {
          $instance->clearCache();
        }
      }
    }
  }
}
?>