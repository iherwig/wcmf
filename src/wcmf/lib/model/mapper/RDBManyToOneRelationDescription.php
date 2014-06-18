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
namespace wcmf\lib\model\mapper;

use wcmf\lib\persistence\RelationDescription;

/**
 * Instances of RDBManyToOneRelationDescription describe a many to one relation
 * from 'this' end (many) to 'other' end (one) in a relational database.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBManyToOneRelationDescription extends RelationDescription {

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
    $hierarchyType, $idName, $fkName) {

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
  public function getIdName() {
    return $this->idName;
  }

  /**
   * Get the name of the foreign key attribute in 'this' end's type
   * @return String
   */
  public function getFkName() {
    return $this->fkName;
  }
}
?>
