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
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\principal\User;

/**
 * OtherSuperUserRole matches if the user has the super user attribute
 * and the resource is a user instance with a different login than the user.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class OtherSuperUserRole {

  private bool $initialized = false;
  private ?PersistenceFacade $persistenceFacade = null;

  /**
   * Constructor
   * @param PersistenceFacade $persistenceFacade
   */
  public function __construct(PersistenceFacade $persistenceFacade) {
    $this->persistenceFacade = $persistenceFacade;
  }

  /**
   * @see DynamicRole::match()
   */
  public function match(User $user, string $resource): bool {
    if ($user->isSuperUser()) {
      $extensionRemoved = preg_replace('/\.[^\.]*?$/', '', $resource);
      if (($oidObj = ObjectId::parse($resource)) !== null || ($oidObj = ObjectId::parse($extensionRemoved)) !== null) {
        if (($obj = $this->persistenceFacade->load($oidObj)) !== null && $obj instanceof \wcmf\lib\security\principal\User) {
          return $user->getLogin() != $obj->getLogin();
        }
      }
    }
    return false;
  }
}
?>
