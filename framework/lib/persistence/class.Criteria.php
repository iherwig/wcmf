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
 * @class Criteria
 * @ingroup Persistence
 * @brief A Criteria defines a condition on a PersistentObject's attribute
 * used to select specific instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Criteria
{
  const OPERATOR_AND = 'AND'; // the and operator
  const OPERATOR_OR = 'OR'; // the or operator

  protected $type = null;
  protected $attribute = null;
  protected $operator = null;
  protected $value = null;
  protected $combineOperator = Criteria::OPERATOR_AND;

  /**
   * Constructor.
   * @param type The PersistentObject type that has the attribute
   * @param attribute The name of the attribute
   * @param operator The comparison operator used to compare the giben value with
   *   the attribute's value
   * @param value The value to compare the object with
   * @param combineOperator The Criteria::OPERATOR to use, when this criteria is
   *   combined with other criteria, optional [default: Criteria::OPERATOR_AND]
   */
  public function __construct($type, $attribute, $operator, $value, $combineOperator=Criteria::OPERATOR_AND)
  {
    $this->type = $type;
    $this->attribute = $attribute;
    $this->operator = $operator;
    $this->value = $value;
    $this->combineOperator = $combineOperator;
  }

  /**
   * Factory method for constructing a Critera that may be used as value on
   * a PersistentObject's attribute (no type, attribute parameter needed)
   * @param operator The comparison operator used to compare the giben value with
   *  the attribute's value
   * @param value The value to compare the object with
   * @return Criteria
   */
  public static function asValue($operator, $value)
  {
    return new Criteria(null, null, $operator, $value);
  }

  /**
   * Set the PersistentObject type that has the attribute
   * @param type The type name
   */
  public function setType($type)
  {
    $this->type = $type;
  }

  /**
   * Get the PersistentObject type that has the attribute
   * @return String
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set the name of the attribute
   * @param attribute The attribute name
   */
  public function setAttribute($attribute)
  {
    $this->attribute = $attribute;
  }

  /**
   * Get the name of the attribute
   * @return String
   */
  public function getAttribute()
  {
    return $this->attribute;
  }

  /**
   * Set the comparison operator used to compare the giben value with
   * the attribute's value
   * @param operator The operator
   */
  public function setOperator($operator)
  {
    $this->operator = $operator;
  }

  /**
   * Get the comparison operator used to compare the giben value with
   * the attribute's value
   * @return String
   */
  public function getOperator()
  {
    return $this->operator;
  }

  /**
   * Set the value to compare the object with
   * @param value The value
   */
  public function setValue($value)
  {
    $this->value = $value;
  }

  /**
   * Get the value to compare the object with
   * @return Mixed
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * Set the Criteria::OPERATOR to use, when this criteria is combined with other criteria
   * @param combineOperator One of the Criteria::OPERATOR constants
   */
  public function setCombineOperator($combineOperator)
  {
    $this->combineOperator = $combineOperator;
  }

  /**
   * Get the Criteria::OPERATOR to use, when this criteria is combined with other criteria
   * @return One of the Criteria::OPERATOR constants
   */
  public function getCombineOperator()
  {
    return $this->combineOperator;
  }

  /**
   * Get a string representation of the operation
   * @return String
   */
  public function __toString()
  {
    $str = "[".$this->combineOperator."] ".$this->type.".".$this->attribute.
            " ".$this->operator." ".$this->value;
    return $str;
  }
}
?>