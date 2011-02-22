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
  protected $type = null;
  protected $attribute = null;
  protected $operator = null;
  protected $value = null;

  /**
   * Constructor.
   * @param type The PersistentObject type that has the attribute
   * @param attribute The name of the attribute
   * @param operator The comparison operator used to compare the giben value with
   *  the attribute's value
   * @param value The value to compare the object with
   */
  public function __construct($type, $attribute, $operator, $value)
  {
    $this->type = $type;
    $this->attribute = $attribute;
    $this->operator = $operator;
    $this->value = $value;
  }

  /**
   * Factory method for constructing a Critera that may be used as value on
   * a PersistentObject's attribute (no type, attribute parameter needed)
   * @param operator The comparison operator used to compare the giben value with
   *  the attribute's value
   * @param value The value to compare the object with
   */
  public static function asValue($operator, $value)
  {
    $this->operator = $operator;
    $this->value = $value;
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
   * Get the name of the attribute
   * @return String
   */
  public function getAttribute()
  {
    return $this->attribute;
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
   * Get the value to compare the object with
   * @return Mixed
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * Get a string representation of the operation
   * @return String
   */
  public function __toString()
  {
    $str = $this->type.".".$this->attribute." ".$this->operator." ".$this->value;
    return $str;
  }
}
?>
