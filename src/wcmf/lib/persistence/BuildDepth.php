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
namespace wcmf\lib\persistence;

/**
 * BuildDepth values are used to define the depth when loading
 * object trees.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BuildDepth {

  const INFINITE = -1;     // build complete tree from given root on
  const SINGLE   = -2;     // build only given object
  const REQUIRED = -4;     // build tree from given root on respecting the required property defined in element relations
  const PROXIES_ONLY = -8; // build only proxies
  const MAX = 10;          // maximum possible creation depth in one call
}
?>
