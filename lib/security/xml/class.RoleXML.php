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
require_once(BASE."wcmf/lib/security/class.RoleImpl.php");

/**
 * @class Role
 * @ingroup Security
 * @brief Implementation of a XML system role.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RoleXML extends RoleImpl
{
  /**
   * Default constructor.
   */
  function RoleXML($oid=null, $type='RoleXML')
  {
    parent::RoleImpl($oid, $type);
  }
}
?>
