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

  private static string $actionDelimiter = '?';

  /**
   * Create an action key from the given values
   * @param string $resource The resource
   * @param string $context The context
   * @param string $action The action
   * @return string
   */
  public static function createKey(?string $resource, ?string $context, ?string $action): string {
    return $resource.self::$actionDelimiter.$context.self::$actionDelimiter.$action;
  }

  /**
   * Parse an action
   * @param string $actionKey The action key
   * @return array{'resource': string, 'context': string, 'action': string}
   */
  public static function parseKey(string $actionKey): array {
    list($resource, $context, $action) = explode(self::$actionDelimiter, $actionKey);
    return ['resource' => $resource, 'context' => $context, 'action' => $action];
  }

  /**
   * Get an action key that matches a given combination of resource, context, action best.
   * @param ActionKeyProvider $actionKeyProvider ActionKeyProvider instance used to search action keys
   * @param string $resource The given resource
   * @param string $context The given context
   * @param string $action The given action
   * @return string The best matching key or an empty string if nothing matches.
   */
  public static function getBestMatch(ActionKeyProvider $actionKeyProvider, ?string $resource, ?string $context, ?string $action): string {
    $hasResource = $resource != null && strlen($resource) > 0;
    $hasContext = $context != null && strlen($context) > 0;
    $hasAction = $action != null && strlen($action) > 0;

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
