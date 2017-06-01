<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal\impl;

use app\src\model\wcmf\User;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\security\principal\DynamicRole;

/**
 * CreatorRole matches if a user created an entity.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CreatorRole {

  const CREATOR_ATTRIBUTE = 'creator';

  private $initialized = false;
  private $permissionManager = null;
  private $persistenceFacade = null;

  /**
   * @see DynamicRole::match()
   */
  public function match(User $user, $resource) {
    $result = null;
    $extensionRemoved = preg_replace('/\.[^\.]*?$/', '', $resource);
    if (($oidObj = ObjectId::parse($resource)) !== null || ($oidObj = ObjectId::parse($extensionRemoved)) !== null) {
      $this->initialize();
      $mapper = $this->persistenceFacade->getMapper($oidObj->getType());
      if ($mapper->hasAttribute(self::CREATOR_ATTRIBUTE)) {
        if ($oidObj->containsDummyIds()) {
          // any user might be the creator of a new object
          $result = true;
        }
        else {
          $tmpPerm = $this->permissionManager->addTempPermission(
                  $oidObj->__toString(), '', PersistenceAction::READ);
          if (($obj = $this->persistenceFacade->load($oidObj)) !== null) {
            $creator = $obj->getValue(self::CREATOR_ATTRIBUTE);
            $result = $creator === $user->getLogin();
          }
          $this->permissionManager->removeTempPermission($tmpPerm);
        }
      }
    }
    return $result;
  }

  private function initialize() {
    if (!$this->initialized) {
      $this->permissionManager = ObjectFactory::getInstance('permissionManager');
      $this->persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $this->initialized = true;
    }
  }
}
?>
