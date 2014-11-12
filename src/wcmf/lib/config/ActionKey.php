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

use wcmf\lib\config\ActionKeyProvider;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Application;

/**
 * An action key is a combination of a resource, context and action that is
 * represented as a string. ActionKey is a helper class for handling
 * action keys.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ActionKey {

  const CACHE_KEY = 'actionkey';

  private static $_actionDelimiter = '?';

  /**
   * Create an action key from the given values
   * @param $resource The resource
   * @param $context The context
   * @param $action The action
   * @return String
   */
  public static function createKey($resource, $context, $action) {
    return $resource.self::$_actionDelimiter.$context.self::$_actionDelimiter.$action;
  }

  /**
   * Parse an action
   * @param $actionKey The action key
   * @return Associative array with keys 'resouce', 'context', 'action'
   */
  public static function parseKey($actionKey) {
    list($resource, $context, $action) = explode(self::$_actionDelimiter, $actionKey);
    return array('resource' => $resource, 'context' => $context, 'action' => $action);
  }

  /**
   * Get an action key that matches a given combination of resource, context, action best.
   * @param $actionKeyProvider ActionKeyProvider instance used to search action keys
   * @param $resource The given resource
   * @param $context The given context
   * @param $action The given action
   * @return The best matching key or an empty string if nothing matches.
   */
  public static function getBestMatch(ActionKeyProvider $actionKeyProvider, $resource, $context, $action) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $authUser = $permissionManager->getAuthUser();
    $cache = ObjectFactory::getInstance('cache');
    // different entries for different providers and logins
    $cacheSection = md5($actionKeyProvider->getCacheId().'_'.($authUser ? $authUser->getLogin() : '').'_'.$_SERVER['SCRIPT_NAME']);

    $cachedKeys = $cache->get(self::CACHE_KEY, $cacheSection);
    if ($cachedKeys == null) {
      $cachedKeys = array();
    }

    // create the requested key
    $reqKey = self::createKey($resource, $context, $action);
    if (!isset($cachedKeys[$reqKey])) {
      $result = null;

      // check resource?context?action
      if (strlen($resource) > 0 && strlen($context) > 0 && strlen($action) > 0) {
        $key = self::createKey($resource, $context, $action);
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // check resource??action
      if ($result === null && strlen($resource) > 0 && strlen($action) > 0) {
        $key = self::createKey($resource, '', $action);
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // check resource?context?
      if ($result === null && strlen($resource) > 0 && strlen($context) > 0) {
        $key = self::createKey($resource, $context, '');
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // check ?context?action
      if ($result === null && strlen($context) > 0 && strlen($action) > 0) {
        $key = self::createKey('', $context, $action);
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // check ??action
      if ($result === null && strlen($action) > 0) {
        $key = self::createKey('', '', $action);
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // check resource??
      if ($result === null && strlen($resource) > 0) {
        $key = self::createKey($resource, '', '');
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // check ?context?
      if ($result === null && strlen($context) > 0) {
        $key = self::createKey('', $context, '');
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // check ??
      if ($result === null) {
        $key = self::createKey('', '', '');
        if ($actionKeyProvider->containsKey($key)) {
          $result = $key;
        }
      }

      // no key found for requested key
      if ($result === null) {
        $result = '';
      }

      // store result for requested key
      $cachedKeys[$reqKey] = $result;
      // don't cache action keys for specific objects
      if (!is_object($resource)) {
        $cache->put(self::CACHE_KEY, $cacheSection, $cachedKeys);
      }
    }
    return $cachedKeys[$reqKey];
  }

  /**
   * Clear the action key cache
   */
  public static function clearCache() {
    $cache = ObjectFactory::getInstance('cache');
    $cache->clear(self::CACHE_KEY);
  }
}
?>
