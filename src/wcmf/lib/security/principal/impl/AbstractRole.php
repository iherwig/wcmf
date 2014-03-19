<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ValidationException;
use wcmf\lib\security\principal\Role;

/**
 * Default implementation of a role.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractRole extends Node implements Role {

  /**
   * @see Role::getByName()
   */
  public static function getByName($name) {
    $roleTypeName = ObjectFactory::getInstance('Role')->getType();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $role = $persistenceFacade->loadFirstObject($roleTypeName, BuildDepth::SINGLE,
                array(
                    new Criteria($roleTypeName, 'name', '=', $name)
                ), null);
    return $role;
  }

  /**
   * @see PersistentObject::validateValue()
   */
  public function validateValue($name, $value) {
    parent::validateValue($name, $value);

    // validate the name property
    // the name is expected to be stored in the 'name' value
    if ($name == 'name') {
      if (strlen(trim($value)) == 0) {
        throw new ValidationException(Message::get("The role requires a name"));
      }
      $role = self::getByName($value);
      if ($role != null && $role->getOID() != $this->getOID()) {
        throw new ValidationException(Message::get("The role '%0%' already exists", array($value)));
      }
    }
  }
}
?>
