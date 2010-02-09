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
require_once(BASE."wcmf/lib/model/class.Node.php");

/**
 * @class Role
 * @ingroup Security
 * @brief Abstract base class for role classes that represent a user role.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class Role extends Node
{
  /**
   * Default constructor.
   */
  public function __construct(ObjectId $oid=null, $type='Role')
  {
    parent::__construct($type, $oid);
  }

  /**
   * Set the name of the role.
   * @param name The name of the role.
   */
  abstract function setName($name);

  /**
   * Get name of the role.
   * @return The name of the role.
   */
  abstract function getName();
}
?>
