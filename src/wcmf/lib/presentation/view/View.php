<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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

  /**
   * Get the template filename for the view from the configfile for the given action key.
   * @param controller The name of the controller
   * @param context The name of the context
   * @param action The name of the action
   * @return The filename of the template or false, if no view is defined
   */
  public static function getTemplate($controller, $context, $action);
}
?>
