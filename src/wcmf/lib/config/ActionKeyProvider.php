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
   * @param string $actionKey ActionKey string
   * @return bool
   */
  public function containsKey(string $actionKey): bool;

  /**
   * Get the value of the given action key.
   * @param string $actionKey ActionKey string
   * @return string or null, if not existing
   */
  public function getKeyValue(string $actionKey): ?string;

  /**
   * Get a string value that uniquely describes the provider configuration.
   * @return string
   */
  public function getId(): string;
}
?>
