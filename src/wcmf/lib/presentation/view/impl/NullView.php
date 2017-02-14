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
    return [];
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

  /**
   * @see View::getCacheDate()
   */
  public static function getCacheDate($tplFile, $cacheId=null) {
    return null;
  }

  /**
   * @see View::getTemplate()
   */
  public static function getTemplate($controller, $context, $action) {
    return null;
  }
}
?>
