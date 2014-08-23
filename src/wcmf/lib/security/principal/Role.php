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
namespace wcmf\lib\security\principal;

/**
 * Role is the interface for user roles.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Role {

  /**
   * Set the name of the role.
   * @param $name The name of the role.
   */
  public function setName($name);

  /**
   * Get name of the role.
   * @return The name of the role.
   */
  public function getName();

  /**
   * Get a Role instance by name.
   * @param $name The role's name
   * @return Role instance.
   */
  public static function getByName($name);
}
?>
