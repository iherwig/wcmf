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
namespace wcmf\lib\presentation\view;

/**
 * View defines the interface for all view implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface View {

  /**
   * Assign a value to the view
   * @param $name The variable name
   * @param $value The value
   */
  public function setValue($name, $value);

  /**
   * Get a value from the view
   * @param $name The variable name
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
   * @param $tplFile The template file
   * @param $cacheId The id of the view (optional, @see Response::getCacheId()) (optional: default: null)
   * @param $cacheLifetime The cache lifetime in seconds (optional, default: implementation specific)
   * @param $display Boolean whether to output the result or return it (default: _true_)
   */
  public function render($tplFile, $cacheId=null, $cacheLifetime=null, $display=true);

  /**
   * Clear the cache
   * @param $tplFile The template file (optional, default: null), null to clear the complete cache
   * @param $cacheId The id of the view (@see Controller::getCacheId()) (optional: default: null)
   * @return Integer number of cache files deleted
   */
  public static function clearCache($tplFile=null, $cacheId=null);

  /**
   * Check if a view is cached
   * @param $tplFile The template file
   * @param $cacheId The id of the view (@see Controller::getCacheId()) (optional: default: null)
   */
  public static function isCached($tplFile, $cacheId=null);

  /**
   * Get the date of the cache entry, if the view is cached
   * @param $tplFile The template file
   * @param $cacheId The id of the view (@see Controller::getCacheId()) (optional: default: null)
   * @return DateTime or null, if not cached
   */
  public static function getCacheDate($tplFile, $cacheId=null);

  /**
   * Get the template filename for the view from the configfile for the given action key.
   * @param $controller The name of the controller
   * @param $context The name of the context
   * @param $action The name of the action
   * @return The filename of the template or false, if no view is defined
   */
  public static function getTemplate($controller, $context, $action);
}
?>
