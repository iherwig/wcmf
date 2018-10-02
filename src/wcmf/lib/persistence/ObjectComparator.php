<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\persistence\PersistentObject;

/**
 * ObjectComparator is used to compare persistent objects by given criterias.
 *
 * The following example shows the usage:
 *
 * @code
 * $objectList = [...]; // array of PersistentObject instances
 *
 * // simple sort by creator attribute
 * $comparator = new ObjectComparator('creator');
 * usort($objectList, [$comparator, 'compare']);
 *
 * // sort by creator attribute with direction
 * $comparator = new ObjectComparator('creator DESC');
 * usort($objectList, [$comparator, 'compare']);
 *
 * // sort by multiple attributes with direction
 * $comparator = new ObjectComparator(['creator DESC', 'created ASC']);
 * usort($objectList, [$comparator, 'compare']);
 *
 * // more complex example with different attributes
 * $sortCriteria = [
 *   ObjectComparator::ATTRIB_TYPE => ObjectComparator::SORTTYPE_ASC,
 *   'created' => ObjectComparator::SORTTYPE_DESC
 * ];
 * $comparator = new ObjectComparator($sortCriteria);
 * usort($objectList, [$comparator, 'compare']);
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectComparator {

  const SORTTYPE_ASC = -1;  // sort children ascending
  const SORTTYPE_DESC = -2; // sort children descending
  const ATTRIB_OID = -3;  // sort by oid
  const ATTRIB_TYPE = -4; // sort by type

  private $sortCriteria = [];

  /**
   * Constructor
   * @param $sortCriteria An assoziative array of criteria - SORTTYPE constant pairs OR a single criteria string.
   *        possible criteria: ObjectComparator::OID, ObjectComparator::TYPE or any value/property name with optionally ASC or DESC appended
   *        (e.g. [ObjectComparator::OID => ObjectComparator::SORTTYPE_ASC, 'name' => ObjectComparator::SORTTYPE_DESC] OR 'name')
   *        @note If criteria is only a string we will sort by this criteria with ObjectComparator::SORTTYPE_ASC
   */
  public function __construct($sortCriteria) {
    // build criteria array
    $criteria = !is_array($sortCriteria) ? [$sortCriteria] : $sortCriteria;
    foreach ($criteria as $attribute => $direction) {
      if (is_int($attribute) && $attribute >= 0) {
        // indexed array of attributes
        $attrDir = explode(' ', $direction);
        $attribute = $attrDir[0];
        $direction = sizeof($attrDir) == 1 ? self::SORTTYPE_ASC :
          (strtoupper(trim($attrDir[1])) == 'DESC' ? self::SORTTYPE_DESC : self::SORTTYPE_ASC);
      }
      $this->sortCriteria[trim($attribute)] = $direction;
    }
  }

  /**
   * Compare function for sorting PersitentObject instances by the list of criterias
   * @param $a First PersitentObject instance
   * @param $b First PersitentObject instance
   * @return -1, 0 or 1 whether a is less, equal or greater than b
   *   in respect of the criteria
   */
  public function compare(PersistentObject $a, PersistentObject $b) {
    // we compare for each criteria and sum the results for $a, $b
    // afterwards we compare the sums and return -1,0,1 appropriate
    $sumA = 0;
    $sumB = 0;
    $maxWeight = sizeOf($this->sortCriteria);
    $i = 0;
    foreach ($this->sortCriteria as $criteria => $sortType) {
      $weightedValue = ($maxWeight-$i)*($maxWeight-$i);
      $aGreaterB = 0;
      // sort by id
      if ($criteria == self::ATTRIB_OID) {
        if ($a->getOID() != $b->getOID()) {
          ($a->getOID() > $b->getOID()) ? $aGreaterB = 1 : $aGreaterB = -1;
        }
      }
      // sort by type
      else if ($criteria == self::ATTRIB_TYPE) {
        if ($a->getType() != $b->getType()) {
          ($a->getType() > $b->getType()) ? $aGreaterB = 1 : $aGreaterB = -1;
        }
      }
      // sort by value
      else if($a->getValue($criteria) != null || $b->getValue($criteria) != null) {
        $aValue = strToLower($a->getValue($criteria));
        $bValue = strToLower($b->getValue($criteria));
        if ($aValue != $bValue) {
          ($aValue > $bValue) ? $aGreaterB = 1 : $aGreaterB = -1;
        }
      }
      // sort by property
      else if($a->getProperty($criteria) != null || $b->getProperty($criteria) != null) {
        $aProperty = strToLower($a->getProperty($criteria));
        $bProperty = strToLower($b->getProperty($criteria));
        if ($aProperty != $bProperty) {
          ($aProperty > $bProperty) ? $aGreaterB = 1 : $aGreaterB = -1;
        }
      }
      // calculate result of current criteria depending on current sorttype
      if ($sortType == self::SORTTYPE_ASC) {
        if ($aGreaterB == 1) { $sumA += $weightedValue; }
        else if ($aGreaterB == -1) { $sumB += $weightedValue; }
      }
      else if ($sortType == self::SORTTYPE_DESC) {
        if ($aGreaterB == 1) { $sumB += $weightedValue; }
        else if ($aGreaterB == -1) { $sumA += $weightedValue; }
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
