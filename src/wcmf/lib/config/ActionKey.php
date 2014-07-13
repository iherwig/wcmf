<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\config;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileCache;

/**
 * ActionKey helps parsing values from action key configurations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ActionKey {

  const CACHE_KEY = 'actionkey';

  private static $_actionDelimiter = '?';

  /**
   * Create an action key from the given values
   * @param resource The resource
   * @param context The context
   * @param action The action
   * @return String
   */
  public static function createKey($resource, $context, $action) {
    return $resource.self::$_actionDelimiter.$context.self::$_actionDelimiter.$action;
  }

  /**
   * Parse an action
   * @param actionKey The action key
   * @return Associative array with keys 'resouce', 'context', 'action'
   */
  public static function parseKey($actionKey) {
    list($resource, $context, $action) = explode(self::$_actionDelimiter, $actionKey);
    return array('resource' => $resource, 'context' => $context, 'action' => $action);
  }

  /**
   * Get a configuration key that matches a given combination of resource, context, action best.
   * @param section The section to search in
   * @param resource The given resource
   * @param context The given context
   * @param action The given action
   * @return The best matching key or an empty string if nothing matches.
   */
  public static function getBestMatch($section, $resource, $context, $action) {
    $cachedKeys = FileCache::get(self::CACHE_KEY, $section);
    if ($cachedKeys == null) {
      $cachedKeys = array();
    }
    $reqKey = self::createKey($resource, $context, $action);
    if (!isset($cachedKeys[$reqKey])) {
      $result = null;
      $config = ObjectFactory::getConfigurationInstance();

      // check resource?context?action
      if (strlen($resource) > 0 && strlen($context) > 0 && strlen($action) > 0) {
        $key = self::createKey($resource, $context, $action);
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // check resource??action
      if ($result == null && strlen($resource) > 0 && strlen($action) > 0) {
        $key = self::createKey($resource, '', $action);
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // check resource?context?
      if ($result == null && strlen($resource) > 0 && strlen($context) > 0) {
        $key = self::createKey($resource, $context, '');
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // check ?context?action
      if ($result == null && strlen($context) > 0 && strlen($action) > 0) {
        $key = self::createKey('', $context, $action);
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // check ??action
      if ($result == null && strlen($action) > 0) {
        $key = self::createKey('', '', $action);
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // check resource??
      if ($result == null && strlen($resource) > 0) {
        $key = self::createKey($resource, '', '');
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // check ?context?
      if ($result == null && strlen($context) > 0) {
        $key = self::createKey('', $context, '');
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // check ??
      if ($result == null) {
        $key = self::createKey('', '', '');
        if ($config->hasValue($key, $section)) {
          $result = $key;
        }
      }

      // not found
      if ($result == null) {
        $result = '';
      }
      $cachedKeys[$reqKey] = $result;
      // don't cache action keys for specific objects
      if (!is_object($resource)) {
        FileCache::put(self::CACHE_KEY, $section, $cachedKeys);
      }
    }
    return $cachedKeys[$reqKey];
  }
}
?>
