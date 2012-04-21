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
namespace wcmf\lib\security;

use wcmf\lib\model\Node;
use wcmf\lib\persistence\ObjectId;

/**
 * Role is the abstract base class for role classes that represent a user role.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class Role extends Node {

  /**
   * Constructor
   * @param oid ObjectId instance (optional)
   */
  public function __construct($oid=null) {
    if ($oid == null) {
      $oid = new ObjectId('Role');
    }
    parent::__construct($oid);
  }

  /**
   * Set the name of the role.
   * @param name The name of the role.
   */
  public abstract function setName($name);

  /**
   * Get name of the role.
   * @return The name of the role.
   */
  public abstract function getName();
}
?>
