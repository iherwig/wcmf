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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\security\principal\Role;
use wcmf\lib\validation\ValidationException;

/**
 * Default implementation of a role.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractRole extends Node implements Role {

  /**
   * @see Role::setName()
   */
  public function setName(string $name): void {
    $this->setValue('name', $name);
  }

  /**
   * @see Role::getName()
   */
  public function getName(): string {
    return $this->getValue('name');
  }

  /**
   * @see PersistentObject::validateValue()
   */
  public function validateValue(string $name, $value): void {
    parent::validateValue($name, $value);

    // validate the name property
    // the name is expected to be stored in the 'name' value
    if ($name == 'name') {
      $message = ObjectFactory::getInstance('message');
      if (strlen(trim($value)) == 0) {
        throw new ValidationException($name, $value, $message->getText("The role requires a name"));
      }
      $principalFactory = ObjectFactory::getInstance('principalFactory');
      $role = $principalFactory->getRole($value);
      if ($role != null && $role->getOID() != $this->getOID()) {
        throw new ValidationException($name, $value, $message->getText("The role '%0%' already exists", [$value]));
      }
    }
  }
}
?>
