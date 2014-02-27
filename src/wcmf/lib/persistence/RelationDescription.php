<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\persistence;

use wcmf\lib\core\ObjectFactory;

/**
 * Instances of RelationDescription describe relations between different types
 * of PersistentObjects. A relation always has two ends: 'this' end and 'other' end.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RelationDescription {

  protected $thisType = '';
  protected $thisRole = '';
  protected $otherType = '';
  protected $otherRole = '';

  protected $thisMinMultiplicity = '0';
  protected $thisMaxMultiplicity = 'unbound';
  protected $otherMinMultiplicity = '0';
  protected $otherMaxMultiplicity = 'unbound';

  protected $thisAggregationKind = 'none';
  protected $otherAggregationKind = 'none';

  protected $thisNavigability = true;
  protected $otherNavigability = true;

  protected $hierarchyType = 'undefined';

  /**
   * Constructor.
   * @param thisType The PersistentObject type at this end
   * @param thisRole The role name at this end
   * @param otherType The PersistentObject type at the other end
   * @param otherRole The role name at the other end
   * @param thisMinMultiplicity The minimum number of instances at this end (number or 'unbound')
   * @param thisMaxMultiplicity The maximum number of instances at this end (number or 'unbound')
   * @param otherMinMultiplicity The minimum number of instances at the other end (number or 'unbound')
   * @param otherMaxMultiplicity The maximum number of instances at the other end (number or 'unbound')
   * @param thisAggregationKind The aggregation kind at this end ('none', 'shared' or 'composite')
   * @param otherAggregationKind The aggregation kind at the other end ('none', 'shared' or 'composite')
   * @param thisNavigability Boolean whether this end is navigable from the other end or not
   * @param otherNavigability Boolean whether the other end is navigable from this end or not
   * @param hierarchyType The hierarchy type that the other end has in relation to this end ('parent', 'child', 'undefined')
   */
  public function __construct($thisType, $thisRole, $otherType, $otherRole, $thisMinMultiplicity, $thisMaxMultiplicity,
    $otherMinMultiplicity, $otherMaxMultiplicity, $thisAggregationKind, $otherAggregationKind, $thisNavigability, $otherNavigability,
    $hierarchyType) {

    $this->thisType = $thisType;
    $this->thisRole = $thisRole;
    $this->otherType = $otherType;
    $this->otherRole = $otherRole;

    $this->thisMinMultiplicity = $thisMinMultiplicity;
    $this->thisMaxMultiplicity = $thisMaxMultiplicity;
    $this->otherMinMultiplicity = $otherMinMultiplicity;
    $this->otherMaxMultiplicity = $otherMaxMultiplicity;

    $this->thisAggregationKind = $thisAggregationKind;
    $this->otherAggregationKind = $otherAggregationKind;

    $this->thisNavigability = $thisNavigability;
    $this->otherNavigability = $otherNavigability;

    $this->hierarchyType = $hierarchyType;
  }

  /**
   * Determine if there may more than one objects at the other side of the relation
   * @return Boolean
   */
  public function isMultiValued() {
    $maxMultiplicity = $this->getOtherMaxMultiplicity();
    if ($maxMultiplicity > 1 || $maxMultiplicity == 'unbounded') {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Get the PersistentObject type at this end
   * @return String
   */
  public function getThisType() {
    return $this->thisType;
  }

  /**
   * Get the PersistentMapper at this end
   * @return PersistenceMapper
   */
  public function getThisMapper() {
    return ObjectFactory::getInstance('persistenceFacade')->getMapper($this->getThisType());
  }

  /**
   * Get the role name at this end
   * @return String
   */
  public function getThisRole() {
    return $this->thisRole;
  }

  /**
   * Get the PersistentObject type at the other end
   * @return String
   */
  public function getOtherType() {
    return $this->otherType;
  }

  /**
   * Get the PersistentMapper at the other end
   * @return PersistenceMapper
   */
  public function getOtherMapper() {
    return ObjectFactory::getInstance('persistenceFacade')->getMapper($this->getOtherType());
  }

  /**
   * Get the role name at the other end
   * @return String
   */
  public function getOtherRole() {
    return $this->otherRole;
  }

  /**
   * Get the minimum number of instances at this end
   * @return Number or 'unbound'
   */
  public function getThisMinMultiplicity() {
    return $this->thisMinMultiplicity;
  }

  /**
   * Get the maximum number of instances at this end
   * @return Number or 'unbound'
   */
  public function getThisMaxMultiplicity() {
    return $this->thisMaxMultiplicity;
  }

  /**
   * Get the minimum number of instances at the other end
   * @return Number or 'unbound'
   */
  public function getOtherMinMultiplicity() {
    return $this->otherMinMultiplicity;
  }

  /**
   * Get the maximum number of instances at the other end
   * @return Number or 'unbound'
   */
  public function getOtherMaxMultiplicity() {
    return $this->otherMaxMultiplicity;
  }

  /**
   * Get the aggregation kind at this end
   * @return String 'none', 'shared' or 'composite'
   */
  public function getThisAggregationKind() {
    return $this->thisAggregationKind;
  }

  /**
   * Get the aggregation kind at the other end
   * @return String 'none', 'shared' or 'composite'
   */
  public function getOtherAggregationKind() {
    return $this->otherAggregationKind;
  }

  /**
   * Check whether this end is navigable from the other end or not
   * @return Boolean
   */
  public function getThisNavigability() {
    return $this->thisNavigability;
  }

  /**
   * Check whether the other end is navigable from this end or not
   * @return Boolean
   */
  public function getOtherNavigability() {
    return $this->otherNavigability;
  }

  /**
   * Get the hierarchy type that the other end has in relation to this end
   * @return String 'parent', 'child', 'undefined'
   */
  public function getHierarchyType() {
    return $this->hierarchyType;
  }

  /**
   * Check if another RelationDescription instance describes the same relation as
   * this one. This is true if they connect the same types using the same role names
   * (independent from the direction). All other attributes are not compared.
   * @param other The other RelationDescription
   * @return Boolean
   */
  public function isSameRelation(RelationDescription $other) {
    if (($this->getThisType() == $other->getThisType() && $this->getOtherType() == $other->getOtherType()
            && $this->getThisRole() == $other->getThisRole() && $this->getOtherRole() == $other->getOtherRole()) ||
        ($this->getThisType() == $other->getOtherType() && $this->getOtherType() == $other->getThisType()
            && $this->getThisRole() == $other->getOtherRole() && $this->getOtherRole() == $other->getThisRole())
       ) {
      return true;
    }
    return false;
  }
}
?>
