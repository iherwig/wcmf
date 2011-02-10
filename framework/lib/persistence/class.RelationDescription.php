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

/**
 * @class RelationDescription
 * @ingroup Persistence
 * @brief Instances of RelationDescription describe relations between different types
 * of PersistentObjects. A relation always has two ends: 'this' end and 'other' end.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RelationDescription
{
  public $thisType = '';
  public $thisRole = '';
  public $otherType = '';
  public $otherRole = '';

  public $thisMinMultiplicity = '0';
  public $thisMaxMultiplicity = 'unbound';
  public $otherMinMultiplicity = '0';
  public $otherMaxMultiplicity = 'unbound';

  public $thisAggregationKind = 'none';
  public $otherAggregationKind = 'none';

  public $thisNavigability = true;
  public $otherNavigability = true;

  public $defaultOrder = '';

  public $hierarchyType = 'undefined';

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
   * @param thisNavigability True/False wether this end is navigable from the other end or not
   * @param otherNavigability True/False wether the other end is navigable from this end or not
   * @param hierarchyType The hierarchy type that the other end has in relation to this end ('parent', 'child', 'undefined')
   */
  public function __construct($thisType, $thisRole, $otherType, $otherRole, $thisMinMultiplicity, $thisMaxMultiplicity,
    $otherMinMultiplicity, $otherMaxMultiplicity, $thisAggregationKind, $otherAggregationKind, $thisNavigability, $otherNavigability,
    $hierarchyType)
  {
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
   * @return boolean
   */
  public function isMultiValued()
  {
    $maxMultiplicity = $this->otherMaxMultiplicity;
    if ($maxMultiplicity > 1 || $maxMultiplicity == 'unbounded') {
      return true;
    }
    else {
      return false;
    }
  }
}
?>
