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
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\principal\User;

/**
 * CreatorRole matches if the user created an entity.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CreatorRole {

  const CREATOR_ATTRIBUTE = 'creator';

  private bool $initialized = false;
  private ?PermissionManager $permissionManager = null;
  private ?PersistenceFacade $persistenceFacade = null;

  /**
   * Constructor
   * @param PermissionManager $permissionManager
   * @param PersistenceFacade $persistenceFacade
   */
  public function __construct(PermissionManager $permissionManager,
          PersistenceFacade $persistenceFacade) {
    $this->permissionManager = $permissionManager;
    $this->persistenceFacade = $persistenceFacade;
  }

  /**
   * @see DynamicRole::match()
   */
  public function match(User $user, string $resource): bool {
    $result = null;
    $extensionRemoved = preg_replace('/\.[^\.]*?$/', '', $resource);
    if (($oidObj = ObjectId::parse($resource)) !== null || ($oidObj = ObjectId::parse($extensionRemoved)) !== null) {
      $mapper = $this->persistenceFacade->getMapper($oidObj->getType());
      if ($mapper->hasAttribute(self::CREATOR_ATTRIBUTE)) {
        if ($oidObj->containsDummyIds()) {
          // any user might be the creator of a new object
          $result = true;
        }
        else {
          $result = $this->permissionManager->withTempPermissions(function() use ($oidObj, $user) {
            if (($obj = $this->persistenceFacade->load($oidObj)) !== null) {
              $creator = $obj->getValue(self::CREATOR_ATTRIBUTE);
              return $creator === $user->getLogin();
            }
            return false;
          }, [$oidObj->__toString(), '', PersistenceAction::READ]);
        }
      }
    }
    return $result;
  }
}
?>
