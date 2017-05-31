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

  const CREATED_ATTRIBUTE = 'creator';

  private $initialized = false;
  private $permissionManager = null;
  private $persistenceFacade = null;

  /**
   * @see DynamicRole::match()
   */
  public function match(User $user, $resource) {
    $result = null;
    if (($oidObj = ObjectId::parse($resource)) !== null) {
      $this->initialize();
      $mapper = $this->persistenceFacade->getMapper($oidObj->getType());
      $tmpPerm = $this->permissionManager->addTempPermission(
              $oidObj->__toString(), '', PersistenceAction::READ);
      if ($mapper->hasAttribute(self::CREATED_ATTRIBUTE) &&
          ($obj = $this->persistenceFacade->load($oidObj)) !== null) {
        $result = $obj->getValue(self::CREATED_ATTRIBUTE) === $user->getLogin();
      }
      $this->permissionManager->removeTempPermission($tmpPerm);
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
