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

/**
 * Implementations of ActionKeyProvider search for action keys.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ActionKeyProvider {

  /**
   * Check if the given action key is existing.
   * @param $actionKey ActionKey string
   * @return Boolean
   */
  public function containsKey($actionKey);

  /**
   * Get the value of the given action key.
   * @param $actionKey ActionKey string
   * @return String or null, if not existing
   */
  public function getKeyValue($actionKey);

  /**
   * Get a string value that uniquely describes the provider configuration.
   * @return String
   */
  public function getId();
}
?>
