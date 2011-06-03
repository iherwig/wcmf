<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
require_once(WCMF_BASE."wcmf/lib/security/class.Role.php");

/**
 * @class Role
 * @ingroup Security
 * @brief Implementation of a system role.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RoleImpl extends Role
{
  /**
   * Default constructor.
   */
  function RoleImpl($oid=null, $type='RoleImpl')
  {
    parent::Role($oid, $type);
  }

  /**
   * Set the name of the role.
   * @param name The name of the role.
   */  
  function setName($name)
  {
    $this->setValue('name', $name);
  }

  /**
   * Get name of the role.
   * @return The name of the role.
   */  
  function getName()
  {
    return $this->getValue('name');
  }
}
?>
