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
require_once(BASE."wcmf/lib/persistence/class.AttributeDescription.php");

/**
 * @class RDBAttributeDescription
 * @ingroup Persistence
 * @brief Instances of RDBAttributeDescription describe attributes of PersistentObjects
 *  in a relational database.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBAttributeDescription extends AttributeDescription
{
  public $column = '';

  /**
   * Constructor.
   * @see AttributeDescription::__construct
   * @param column The column name
   */
  public function __construct($name, $type, array $appDataTypes, $defaultValue, $restrictionsMatch, $restrictionsNotMatch,
    $restrictionsDescription, $isEditable, $inputType, $displayType, $column)
  {
    parent::__construct($name, $type, $appDataTypes, $defaultValue, $restrictionsMatch, $restrictionsNotMatch,
      $restrictionsDescription, $isEditable, $inputType, $displayType);

    $this->column = $column;
  }
}
?>
