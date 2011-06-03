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
require_once(WCMF_BASE."wcmf/lib/persistence/class.AttributeDescription.php");

/**
 * @class RDBAttributeDescription
 * @ingroup Persistence
 * @brief Instances of RDBAttributeDescription describe attributes of PersistentObjects
 * in a relational database.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBAttributeDescription extends AttributeDescription
{
  protected $table = '';
  protected $column = '';

  /**
   * Constructor.
   * @see AttributeDescription::__construct
   * @param table The table name
   * @param column The column name
   */
  public function __construct($name, $type, array $tags, $defaultValue, $restrictionsMatch, $restrictionsNotMatch,
    $restrictionsDescription, $isEditable, $inputType, $displayType, $table, $column)
  {
    parent::__construct($name, $type, $tags, $defaultValue, $restrictionsMatch, $restrictionsNotMatch,
      $restrictionsDescription, $isEditable, $inputType, $displayType);

    $this->table = $table;
    $this->column = $column;
  }

  /**
   * Get the table name
   * @return String
   */
  public function getTable()
  {
    return $this->table;
  }

  /**
   * Get the column name
   * @return String
   */
  public function getColumn()
  {
    return $this->column;
  }
}
?>
