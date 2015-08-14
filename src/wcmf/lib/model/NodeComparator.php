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
namespace wcmf\lib\model;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\model\Node;

/**
 * NodeComparator is used to compare nodes by given criterias.
 *
 * The following example shows the usage:
 *
 * @code
 * $nodeList = array(...); // array of Node instances
 *
 * // simple sort by creator attribute
 * $comparator = new NodeComparator('creator');
 * usort($nodeList, array($comparator, 'compare'));
 *
 * // more complex example with different attributes
 * $sortCriteria = array(
 *   NodeComparator::ATTRIB_TYPE => NodeComparator::SORTTYPE_ASC,
 *   'created' => NodeComparator::SORTTYPE_DESC
 * );
 * $comparator = new NodeComparator($sortCriteria);
 * usort($nodeList, array($comparator, 'compare'));
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeComparator {

  const SORTTYPE_ASC = -1;  // sort children ascending
  const SORTTYPE_DESC = -2; // sort children descending
  const ATTRIB_OID = -3;  // sort by oid
  const ATTRIB_TYPE = -4; // sort by type

  private $_sortCriteria;

  /**
   * Constructor
   * @param $sortCriteria An assoziative array of criteria - SORTTYPE constant pairs OR a single criteria string.
   *        possible criteria: NodeComparator::OID, NodeComparator::TYPE or any value/property name
   *        (e.g. array(NodeComparator::OID => NodeComparator::SORTTYPE_ASC, 'name' => NodeComparator::SORTTYPE_DESC) OR 'name')
   *        @note If criteria is only a string we will sort by this criteria with NodeComparator::SORTTYPE_ASC
   */
  public function __construct(array $sortCriteria) {
    $this->_sortCriteria = $sortCriteria;
  }

  /**
   * Compare function for sorting Nodes by the list of criterias
   * @param $a First Node instance
   * @param $b First Node instance
   * @return -1, 0 or 1 whether a is less, equal or greater than b
   *   in respect of the criteria
   */
  public function compare(Node $a, Node $b) {
    // we compare for each criteria and sum the results for $a, $b
    // afterwards we compare the sums and return -1,0,1 appropriate
    $sumA = 0;
    $sumB = 0;
    $maxWeight = sizeOf($this->_sortCriteria);
    $i = 0;
    foreach ($this->_sortCriteria as $criteria => $sortType) {
      $weightedValue = ($maxWeight-$i)*($maxWeight-$i);
      $AGreaterB = 0;
      // sort by id
      if ($criteria == self::ATTRIB_OID) {
        if ($a->getOID() != $b->getOID()) {
          ($a->getOID() > $b->getOID()) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // sort by type
      else if ($criteria == self::ATTRIB_TYPE) {
        if ($a->getType() != $b->getType()) {
          ($a->getType() > $b->getType()) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // sort by value
      else if($a->getValue($criteria) != null || $b->getValue($criteria) != null) {
        $aValue = strToLower($a->getValue($criteria));
        $bValue = strToLower($b->getValue($criteria));
        if ($aValue != $bValue) {
          ($aValue > $bValue) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // sort by property
      else if($a->getProperty($criteria) != null || $b->getProperty($criteria) != null) {
        $aProperty = strToLower($a->getProperty($criteria));
        $bProperty = strToLower($b->getProperty($criteria));
        if ($aProperty != $bProperty) {
          ($aProperty > $bProperty) ? $AGreaterB = 1 : $AGreaterB = -1;
        }
      }
      // calculate result of current criteria depending on current sorttype
      if ($sortType == self::SORTTYPE_ASC) {
        if ($AGreaterB == 1) { $sumA += $weightedValue; }
        else if ($AGreaterB == -1) { $sumB += $weightedValue; }
      }
      else if ($sortType == self::SORTTYPE_DESC) {
        if ($AGreaterB == 1) { $sumB += $weightedValue; }
        else if ($AGreaterB == -1) { $sumA += $weightedValue; }
      }
      else {
        throw new IllegalArgumentException("Unknown SORTTYPE.");
      }
      $i++;
    }
    if ($sumA == $sumB) { return 0; }
    return ($sumA > $sumB) ? 1 : -1;
  }
}
?>
