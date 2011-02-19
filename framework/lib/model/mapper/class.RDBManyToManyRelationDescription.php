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
require_once(WCMF_BASE."wcmf/lib/persistence/class.RelationDescription.php");

/**
 * @class RDBManyToManyRelationDescription
 * @ingroup Persistence
 * @brief Instances of RDBManyToManyRelationDescription describe a many to many relation
 * from 'this' end to 'other' end  in a relational database.
 * This relation is always realized by a connecting database table and can be resolved
 * into a many-to-one relation from 'this' end to the relation type and a one-to-many relation
 * from the relation type to the 'other' end.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBManyToManyRelationDescription extends RelationDescription
{
  protected $thisEndRelation = '';
  protected $otherEndRelation = '';

  /**
   * Constructor.
   * @see RelationDescription::__construct
   * @param thisEndRelation The RDBOneToManyRelationDescription describing the relation between 'this' end and the connecting type
   * @param oneToManyRelationDescription The RDBManyToOneRelationDescription describing the relation between the connecting type and the 'other' end
   */
  public function __construct(RDBOneToManyRelationDescription $thisEndRelation, RDBManyToOneRelationDescription $otherEndRelation)
  {
    $this->thisEndRelation = $thisEndRelation;
    $this->otherEndRelation = $otherEndRelation;
  }

  /**
   * Get the RDBOneToManyRelationDescription describing the relation between
   * 'this' end and the connecting type
   * @return RelationDescription
   */
  public function getThisEndRelation()
  {
    return $this->thisEndRelation;
  }

  /**
   * Get the RDBManyToOneRelationDescription describing the relation between
   * the connecting type and the 'other' end
   * @return RelationDescription
   */
  public function getOtherEndRelation()
  {
    return $this->otherEndRelation;
  }

  /**
   * @see RelationDescription::isMultiValued
   */
  public function isMultiValued()
  {
    return true;
  }

  /**
   * Get the PersistentObject type at this end
   * @return String
   */
  public function getThisType()
  {
    return $this->thisEndRelation->thisType;
  }

  /**
   * Get the role name at this end
   * @return String
   */
  public function getThisRole()
  {
    return $this->thisEndRelation->thisRole;
  }

  /**
   * Get the PersistentObject type at the other end
   * @return String
   */
  public function getOtherType()
  {
    return $this->otherEndRelation->otherType;
  }

  /**
   * Get the role name at the other end
   * @return String
   */
  public function getOtherRole()
  {
    return $this->otherEndRelation->otherRole;
  }

  /**
   * Get the minimum number of instances at this end
   * @return Number or 'unbound'
   */
  public function getThisMinMultiplicity()
  {
    return $this->thisEndRelation->thisMinMultiplicity;
  }

  /**
   * Get the maximum number of instances at this end
   * @return Number or 'unbound'
   */
  public function getThisMaxMultiplicity()
  {
    return $this->thisEndRelation->thisMaxMultiplicity;
  }

  /**
   * Get the minimum number of instances at the other end
   * @return Number or 'unbound'
   */
  public function getOtherMinMultiplicity()
  {
    return $this->thisEndRelation->otherMinMultiplicity;
  }

  /**
   * Get the maximum number of instances at the other end
   * @return Number or 'unbound'
   */
  public function getOtherMaxMultiplicity()
  {
    return $this->thisEndRelation->otherMaxMultiplicity;
  }

  /**
   * Get the aggregation kind at this end
   * @return String 'none', 'shared' or 'composite'
   */
  public function getThisAggregationKind()
  {
    return $this->thisEndRelation->thisAggregationKind;
  }

  /**
   * Get the aggregation kind at the other end
   * @return String 'none', 'shared' or 'composite'
   */
  public function getOtherAggregationKind()
  {
    return $this->thisEndRelation->otherAggregationKind;
  }

  /**
   * Check wether this end is navigable from the other end or not
   * @return Boolean
   */
  public function getThisNavigability()
  {
    return $this->thisEndRelation->thisNavigability;
  }

  /**
   * Check wether the other end is navigable from this end or not
   * @return Boolean
   */
  public function getOtherNavigability()
  {
    return $this->thisEndRelation->otherNavigability;
  }

  /**
   * Get the hierarchy type that the other end has in relation to this end
   * @return String 'parent', 'child', 'undefined'
   */
  public function getHierarchyType()
  {
    return 'child';
  }
}
?>
