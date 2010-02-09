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
require_once(BASE."wcmf/lib/persistence/class.RelationDescription.php");

/**
 * @class RDBManyToOneRelationDescription
 * @ingroup Persistence
 * @brief Instances of RDBManyToOneRelationDescription describe a many to one relation
 * from 'this' end (many) to 'other' end (one) in a relational database.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBManyToOneRelationDescription extends RelationDescription
{
  public $thisTable = '';
  public $otherTable = '';

  public $idColumn = '';
  public $fkColumn = '';

  /**
   * Constructor.
   * @see RelationDescription::__construct
   * @param thisTable The database table name of 'this' end
   * @param otherTable The database table name of the 'other' end
   * @param idColumn The name of the column in the 'other' end's table, that is referenced by the foreign key column in 'this' end's table
   * @param fkColumn The name of the foreign key column in 'this' end's table
   */
  public function __construct($thisType, $thisRole, $otherType, $otherRole, $thisMinMultiplicity, $thisMaxMultiplicity,
    $otherMinMultiplicity, $otherMaxMultiplicity, $thisAggregationKind, $otherAggregationKind, $thisNavigability, $otherNavigability,
    $hierarchyType, $thisTable, $otherTable, $idColumn, $fkColumn)
  {
    parent::__construct($thisType, $thisRole, $otherType, $otherRole, $thisMinMultiplicity, $thisMaxMultiplicity,
    $otherMinMultiplicity, $otherMaxMultiplicity, $thisAggregationKind, $otherAggregationKind, $thisNavigability, $otherNavigability,
    $hierarchyType);

    $this->thisTable = $thisTable;
    $this->otherTable = $otherTable;
    $this->idColumn = $idColumn;
    $this->fkColumn = $fkColumn;
  }
}
?>
