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
namespace wcmf\lib\config;

use wcmf\lib\config\ActionKeyProvider;

/**
 * An action key is a combination of a resource, context and action that is
 * represented as a string. ActionKey is a helper class for handling
 * action keys.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ActionKey {

  private static $actionDelimiter = '?';

  /**
   * Create an action key from the given values
   * @param $resource The resource
   * @param $context The context
   * @param $action The action
   * @return String
   */
  public static function createKey($resource, $context, $action) {
    return $resource.self::$actionDelimiter.$context.self::$actionDelimiter.$action;
  }

  /**
   * Parse an action
   * @param $actionKey The action key
   * @return Associative array with keys 'resouce', 'context', 'action'
   */
  public static function parseKey($actionKey) {
    list($resource, $context, $action) = explode(self::$actionDelimiter, $actionKey);
    return ['resource' => $resource, 'context' => $context, 'action' => $action];
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
    $hasResource = strlen($resource ?? '') > 0;
    $hasContext = strlen($context ?? '') > 0;
    $hasAction = strlen($action ?? '') > 0;

    // check resource?context?action
    if ($hasResource && $hasContext && $hasAction) {
      $key = self::createKey($resource, $context, $action);
      if ($actionKeyProvider->containsKey($key)) {
        return $key;
      }
    }

    // check resource??action
    if ($hasResource && $hasAction) {
      $key = self::createKey($resource, '', $action);
      if ($actionKeyProvider->containsKey($key)) {
        return $key;
      }
    }

    // check resource?context?
    if ($hasResource && $hasContext) {
      $key = self::createKey($resource, $context, '');
      if ($actionKeyProvider->containsKey($key)) {
        return $key;
      }
    }

    // check ?context?action
    if ($hasContext && $hasAction) {
      $key = self::createKey('', $context, $action);
      if ($actionKeyProvider->containsKey($key)) {
        return $key;
      }
    }

    // check ??action
    if ($hasAction) {
      $key = self::createKey('', '', $action);
      if ($actionKeyProvider->containsKey($key)) {
        return $key;
      }
    }

    // check resource??
    if ($hasResource) {
      $key = self::createKey($resource, '', '');
      if ($actionKeyProvider->containsKey($key)) {
        return $key;
      }
    }

    // check ?context?
    if ($hasContext) {
      $key = self::createKey('', $context, '');
      if ($actionKeyProvider->containsKey($key)) {
        return $key;
      }
    }

    // check ??
    $key = self::createKey('', '', '');
    if ($actionKeyProvider->containsKey($key)) {
      return $key;
    }

    // no key found for requested key
    return '';
  }
}
?>
