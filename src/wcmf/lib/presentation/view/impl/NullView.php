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
namespace wcmf\lib\presentation\view\impl;

use wcmf\lib\presentation\view\View;

/**
 * NullView is a stub class that implements all view methods.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullView implements View {

  /**
   * @see View::setValue()
   */
  public function setValue($name, $value) {}

  /**
   * @see View::getValue()
   */
  public function getValue($name) {
    return null;
  }

  /**
   * @see View::getValues()
   */
  public function getValues() {
    return array();
  }

  /**
   * @see View::clearAllValues()
   */
  public function clearAllValues() {}

  /**
   * @see View::display()
   */
  public function render($tplFile, $cacheId=null, $display=true) {
    if (!$display) {
      return '';
    }
  }

  /**
   * @see View::clearCache()
   */
  public static function clearCache() {
    return 0;
  }

  /**
   * @see View::isCached()
   */
  public static function isCached($tplFile, $cacheId=null) {
    return false;
  }
}
?>
