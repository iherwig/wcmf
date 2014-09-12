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

/**
 * Implementations of ActionKeyProvider search action keys from
 * their underlying storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ActionKeyProvider {

  /**
   * Check if the given action key is contained in the storage.
   * @param $actionKey ActionKey string
   * @return Boolean
   */
  public function containsKey($actionKey);

  /**
   * Get a string value that uniquely describes the provider configuration.
   * @return String
   */
  public function getCacheId();
}
?>
