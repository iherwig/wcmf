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
 * $Id: class.NullView.php 1148 2010-02-09 02:08:44Z iherwig $
 */
namespace wcmf\lib\presentation\view;

/**
 * View defines the interface for all view implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface View {

  /**
   * Assign a value to the view
   * @param name The variable name
   * @param value The value
   */
  public function setValue($name, $value);

  /**
   * Get a value from the view
   * @param name The variable name
   * @return Mixed
   */
  public function getValue($name);

  /**
   * Get all values from the view
   * @return Array
   */
  public function getValues();

  /**
   * Clear all values in the view
   */
  public function clearAllValues();

  /**
   * Render the given template
   * @param tplFile The template file
   * @param cacheId The id of the view (@see Controller::getCacheId())
   * @param display Boolean whether to output the result or return it [default: true]
   */
  public function render($tplFile, $cacheId=null, $display=true);

  /**
   * Clear the cache
   * @return Integer number of cache files deleted
   */
  public static function clearCache();

  /**
   * Check if a view is cached
   * @param tplFile The template file
   * @param cacheId The id of the view (@see Controller::getCacheId())
   */
  public static function isCached($tplFile, $cacheId=null);
}
?>
