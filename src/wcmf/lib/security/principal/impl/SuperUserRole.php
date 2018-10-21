<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal\impl;

use app\src\model\wcmf\User;
use wcmf\lib\security\principal\DynamicRole;

/**
 * SuperUserRole matches if the user has the super user attribute.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SuperUserRole {

  /**
   * @see DynamicRole::match()
   */
  public function match(User $user, $resource) {
    return $user->isSuperUser();
  }
}
?>
