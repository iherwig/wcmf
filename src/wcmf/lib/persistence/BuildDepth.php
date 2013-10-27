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
