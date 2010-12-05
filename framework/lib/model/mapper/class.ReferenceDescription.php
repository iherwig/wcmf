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
 * @class ReferenceDescription
 * @ingroup Persistence
 * @brief Instances of ReferenceDescription describe reference attributes of PersistentObjects
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ReferenceDescription extends AttributeDescription
{
  private $_attributeDescription = null;

  public $otherType = '';
  public $otherName = '';

  /**
   * Constructor.
   * @param name The name of the reference
   * @param otherType The name of the referenced type (must be the role name, @see RelationDescription)
   * @param otherName The name of the referenced attribute in the referenced type
   */
  public function __construct($name, $otherType, $otherName)
  {
    $this->name = $name;
    $this->otherType = $otherType;
    $this->otherName = $otherName;
  }

  /**
   * Delegate property access to the base class.
   */
  public function __get($propName)
  {
    if ($this->_attributeDescription == null)
    {
      // get the AttributeDescription of the referenced attribute and fill in the missing attributes
      $mapper = PersistenceFacade::getInstance()->getMapper($this->otherType);
      $this->_attributeDescription = $mapper->getAttribute($this->otherName);
    }
    return $this->_attributeDescription->$propName;
  }
}
?>
