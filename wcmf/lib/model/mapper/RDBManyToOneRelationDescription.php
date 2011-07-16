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
require_once(WCMF_BASE."wcmf/lib/persistence/RelationDescription.php");

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
  protected $idName = '';
  protected $fkName = '';

  /**
   * Constructor.
   * @see RelationDescription::__construct
   * @param idName The name of the attribute in the 'other' end's type, that is referenced by the foreign key attribute in 'this' end's type
   * @param fkName The name of the foreign key attribute in 'this' end's type
   */
  public function __construct($thisType, $thisRole, $otherType, $otherRole, $thisMinMultiplicity, $thisMaxMultiplicity,
    $otherMinMultiplicity, $otherMaxMultiplicity, $thisAggregationKind, $otherAggregationKind, $thisNavigability, $otherNavigability,
    $hierarchyType, $idName, $fkName)
  {
    parent::__construct($thisType, $thisRole, $otherType, $otherRole, $thisMinMultiplicity, $thisMaxMultiplicity,
    $otherMinMultiplicity, $otherMaxMultiplicity, $thisAggregationKind, $otherAggregationKind, $thisNavigability, $otherNavigability,
    $hierarchyType);

    $this->idName = $idName;
    $this->fkName = $fkName;
  }

  /**
   * Get the name of the attribute in the 'other' end's type, that is referenced
   * by the foreign key attribute in 'this' end's type
   * @return String
   */
  public function getIdName()
  {
    return $this->idName;
  }

  /**
   * Get the name of the foreign key attribute in 'this' end's type
   * @return String
   */
  public function getFkName()
  {
    return $this->fkName;
  }
}
?>
