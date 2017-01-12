<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
use wcmf\lib\model\mapper\RDBOneToManyRelationDescription;
use wcmf\lib\persistence\RelationDescription;

/**
 * Instances of RDBManyToManyRelationDescription describe a many to many relation
 * from 'this' end to 'other' end  in a relational database.
 * This relation is always realized by a connecting database table and can be resolved
 * into a many-to-one relation from 'this' end to the relation type and a one-to-many relation
 * from the relation type to the 'other' end.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBManyToManyRelationDescription extends RelationDescription {

  protected $thisEndRelation = '';
  protected $otherEndRelation = '';

  /**
   * Constructor.
   * @see RelationDescription::__construct
   * @param $thisEndRelation The RDBOneToManyRelationDescription describing the relation between 'this' end and the connecting type
   * @param $otherEndRelation The RDBManyToOneRelationDescription describing the relation between the connecting type and the 'other' end
   */
  public function __construct(RDBOneToManyRelationDescription $thisEndRelation, RDBManyToOneRelationDescription $otherEndRelation) {
    $this->thisEndRelation = $thisEndRelation;
    $this->otherEndRelation = $otherEndRelation;
  }

  /**
   * Get the RDBOneToManyRelationDescription describing the relation between
   * 'this' end and the connecting type
   * @return RelationDescription
   */
  public function getThisEndRelation() {
    return $this->thisEndRelation;
  }

  /**
   * Get the RDBManyToOneRelationDescription describing the relation between
   * the connecting type and the 'other' end
   * @return RelationDescription
   */
  public function getOtherEndRelation() {
    return $this->otherEndRelation;
  }

  /**
   * @see RelationDescription::isMultiValued
   */
  public function isMultiValued() {
    return true;
  }

  /**
   * @see RelationDescription::getThisType
   */
  public function getThisType() {
    return $this->thisEndRelation->thisType;
  }

  /**
   * @see RelationDescription::getThisRole
   */
  public function getThisRole() {
    return $this->thisEndRelation->thisRole;
  }

  /**
   * @see RelationDescription::getOtherType
   */
  public function getOtherType() {
    return $this->otherEndRelation->otherType;
  }

  /**
   * @see RelationDescription::getOtherRole
   */
  public function getOtherRole() {
    return $this->otherEndRelation->otherRole;
  }

  /**
   * @see RelationDescription::getThisMinMultiplicity
   */
  public function getThisMinMultiplicity() {
    return $this->thisEndRelation->thisMinMultiplicity;
  }

  /**
   * @see RelationDescription::getThisMaxMultiplicity
   */
  public function getThisMaxMultiplicity() {
    return $this->thisEndRelation->thisMaxMultiplicity;
  }

  /**
   * @see RelationDescription::getOtherMinMultiplicity
   */
  public function getOtherMinMultiplicity() {
    return $this->thisEndRelation->otherMinMultiplicity;
  }

  /**
   * @see RelationDescription::getOtherMaxMultiplicity
   */
  public function getOtherMaxMultiplicity() {
    return $this->thisEndRelation->otherMaxMultiplicity;
  }

  /**
   * @see RelationDescription::getThisAggregationKind
   */
  public function getThisAggregationKind() {
    return $this->thisEndRelation->thisAggregationKind;
  }

  /**
   * @see RelationDescription::getOtherAggregationKind
   */
  public function getOtherAggregationKind() {
    return $this->otherEndRelation->otherAggregationKind;
  }

  /**
   * @see RelationDescription::getThisNavigability
   */
  public function getThisNavigability() {
    return $this->thisEndRelation->thisNavigability;
  }

  /**
   * @see RelationDescription::getOtherNavigability
   */
  public function getOtherNavigability() {
    return $this->otherEndRelation->otherNavigability;
  }

  /**
   * @see RelationDescription::getHierarchyType
   */
  public function getHierarchyType() {
    return 'child';
  }
}
?>
