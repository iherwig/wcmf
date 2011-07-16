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
require_once(WCMF_BASE."wcmf/lib/model/Node.php");

/**
 * @class NullNode
 * @ingroup Model
 * @brief NullNode is an implementation of the NullObject pattern,
 * It inherits all functionality from Node (acts like a Node)
 * and is only distinguishable from a Node instance by it's class or oid.
 * If a Node's parent is a NullNode instance, than they should be separated
 * in the data store (e.g. the foreign key should be null, if allowed by the database).
 * NullNode child instances should be ignored.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullNode extends Node
{
  /**
   * @see PersistentObject::getOID()
   */
  function getOID()
  {
    return new ObjectId($this->_type, NULL);
  }
}
?>
