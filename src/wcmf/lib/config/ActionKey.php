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

/**
 * An action key is a combination of a resource, context and action that is
 * represented as a string. ActionKey is a helper class for handling
 * action keys.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ActionKey {

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
    $result = null;
    $hasResource = strlen($resource) > 0;
    $hasContext = strlen($context) > 0;
    $hasAction = strlen($action) > 0;

    // check resource?context?action
    if ($hasResource && $hasContext && $hasAction) {
      $key = self::createKey($resource, $context, $action);
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // check resource??action
    elseif ($hasResource && $hasAction) {
      $key = self::createKey($resource, '', $action);
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // check resource?context?
    elseif ($hasResource && $hasContext) {
      $key = self::createKey($resource, $context, '');
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // check ?context?action
    elseif ($hasContext && $hasAction) {
      $key = self::createKey('', $context, $action);
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // check ??action
    elseif ($hasAction) {
      $key = self::createKey('', '', $action);
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // check resource??
    elseif ($hasResource) {
      $key = self::createKey($resource, '', '');
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // check ?context?
    elseif ($hasContext) {
      $key = self::createKey('', $context, '');
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // check ??
    else {
      $key = self::createKey('', '', '');
      if ($actionKeyProvider->containsKey($key)) {
        $result = $key;
      }
    }

    // no key found for requested key
    if ($result === null) {
      $result = '';
    }

    return $result;
  }
}
?>
