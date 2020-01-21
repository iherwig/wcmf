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
namespace wcmf\lib\security\principal;

use wcmf\lib\security\principal\User;

/**
 * DynamicRole is the interface for user roles based on attributes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface DynamicRole {

  /**
   * Check if this role matches for a user and resource.
   * @param $user The user instance.
   * @param $resource The resource string.
   * @return Boolean whether the role matches or not, null if the result is undefined
   */
  public function match(User $user, $resource);
}
?>
