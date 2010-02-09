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
require_once(BASE."wcmf/3rdparty/adodb/adodb-active-record.inc.php");

/**
 * @class ActiveRecord
 * @ingroup Mapper
 * @brief ActiveRecord adds roles to ADOdb's Active Records.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ActiveRecord extends ADODB_Active_Record
{
  /**
   * Add a one-to-many reference (child) with a role name.
   * @param foreignRef The child table name
   * @param foreignKey The foreign key in the child table pointing to this table
   * @param foreignClass A subclass of ADODB_Active_Record that represents the child table for the given role
   * @param foreignRole The name of the attribute that will hold the child references in this class
   */
  function hasManyRole($foreignRef, $foreignKey, $foreignClass, $foreignRole)
  {
    $ar = new $foreignClass($foreignRef);
    $ar->foreignName = $foreignRef;
    $ar->UpdateActiveTable();
    $ar->foreignKey = $foreignKey;
    $table =& $this->TableInfo();
    $table->_hasMany[$foreignRole] = $ar;
  }
  /**
   * Add a many-to-one reference (parent) with a role name.
   * @param foreignRef The parent table name
   * @param foreignKey The foreign key in this table pointing to the parent table
   * @param parentKey The primary key in the parent table
   * @param parentClass A subclass of ADODB_Active_Record that represents the parent table for the given role
   * @param foreignRole The name of the attribute that will hold the parent reference in this class
   */
  function belongsToRole($foreignRef, $foreignKey, $parentKey, $parentClass, $foreignRole)
  {
    $ar = new $parentClass($foreignRef);
    $ar->foreignName = $foreignRef;
    $ar->parentKey = $parentKey;
    $ar->UpdateActiveTable();
    $ar->foreignKey = $foreignKey;

    $table =& $this->TableInfo();
    $table->_belongsTo[$foreignRole] = $ar;
  }
}
?>