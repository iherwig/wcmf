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

use wcmf\lib\util\StringUtil;

/**
 * A PersistenceOperation instance holds data necessary to accomplish
 * an operation on the persistent store.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class PersistenceOperation {

  protected $type = null;
  protected $values = null;
  protected $criteria = null;

  /**
   * Constructor.
   * @param $type The type of PersistentObject on which the operation
   *          should be executed
   * @param $values An array of attribute/value pairs to apply
   * @param $criteria An array of criteria instances to select the objects on
   *          which the operation will be executed
   */
  public function __construct($type, array $values, array $criteria) {
    $this->type = $type;
    $this->values = $values;
    $this->criteria = $criteria;
  }

  /**
   * Get the type of PersistentObject on which the operation should be executed
   * @return String
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get values to apply
   * @return Array of attribute/value pairs
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * Get criteria to match
   * @return Array of Criteria instances
   */
  public function getCriteria() {
    return $this->criteria;
  }

  /**
   * Get a string representation of the operation
   * @return String
   */
  public function __toString() {
    $str = get_class($this).":type=".$this->type.",values=(";
    foreach($this->values as $key => $val) {
      $str .= $key."=".$val.",";
    }
    $str = StringUtil::removeTrailingComma($str);
    $str .= "),criteria=(";
    foreach($this->criteria as $criteria) {
      $str .= $criteria->__toString();
    }
    $str = StringUtil::removeTrailingComma($str);
    $str .= ")";
    return $str;
  }
}
?>
