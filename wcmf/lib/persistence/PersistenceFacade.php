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

use wcmf\lib\core\ObjectFactory;

/**
 * Some constants describing the build process
 */
// TODO: make them constants in PersistenceFacade
define("BUILDDEPTH_INFINITE", -1);     // build complete tree from given root on
define("BUILDDEPTH_SINGLE",   -2);     // build only given object
define("BUILDDEPTH_REQUIRED", -4);     // build tree from given root on respecting the required property defined in element relations
define("BUILDDEPTH_PROXIES_ONLY", -8); // build only proxies
define("BUILDDEPTH_MAX", 10);          // maximum possible creation depth in one call

/**
 * PersistenceFacade instantiates the PersistenceFacade implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceFacade {

  private static $_instance = null;

  private function __construct() {}

  /**
   * Returns an instance of the PersistenceFacade implementation.
   * @return IPersistenceFacade
   */
  public static function getInstance() {
    if (!isset(self::$_instance)) {
      self::$_instance = ObjectFactory::createInstanceFromConfig('implementation', 'PersistenceFacade');
    }
    return self::$_instance;
  }
}
?>
