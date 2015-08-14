<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use wcmf\lib\persistence\RelationDescription;

/**
 * Instances of RDBOneToManyRelationDescription describe a one to many relation
 * from 'this' end (one) to 'other' end (many) in a relational database.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBOneToManyRelationDescription extends RelationDescription {

  protected $idName = '';
  protected $fkName = '';

  /**
   * Constructor.
   * @param $thisType The PersistentObject type at this end
   * @param $thisRole The role name at this end
   * @param $otherType The PersistentObject type at the other end
   * @param $otherRole The role name at the other end
   * @param $thisMinMultiplicity The minimum number of instances at this end (number or 'unbound')
   * @param $thisMaxMultiplicity The maximum number of instances at this end (number or 'unbound')
   * @param $otherMinMultiplicity The minimum number of instances at the other end (number or 'unbound')
   * @param $otherMaxMultiplicity The maximum number of instances at the other end (number or 'unbound')
   * @param $thisAggregationKind The aggregation kind at this end ('none', 'shared' or 'composite')
   * @param $otherAggregationKind The aggregation kind at the other end ('none', 'shared' or 'composite')
   * @param $thisNavigability Boolean whether this end is navigable from the other end or not
   * @param $otherNavigability Boolean whether the other end is navigable from this end or not
   * @param $hierarchyType The hierarchy type that the other end has in relation to this end ('parent', 'child', 'undefined')
   * @param $idName The name of the attribute in 'this' end's type, that is referenced by the foreign key attribute in the 'other' end's type
   * @param $fkName The name of the foreign key attribute in the 'other' end's type
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
   * Get the name of the attribute in 'this' end's type, that is referenced by
   * the foreign key attribute in the 'other' end's type
   * @return String
   */
  public function getIdName() {
    return $this->idName;
  }

  /**
   * Get the name of the foreign key attribute in the 'other' end's type
   * @return String
   */
  public function getFkName() {
    return $this->fkName;
  }
}
?>
