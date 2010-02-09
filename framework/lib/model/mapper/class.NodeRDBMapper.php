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
require_once(BASE."wcmf/lib/model/mapper/class.RDBMapper.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(BASE."wcmf/lib/model/class.Node.php");

/**
 * @class NodeRDBMapper
 * @ingroup Mapper
 * @brief NodeRDBMapper maps Node objects to a relational database schema where each Node
 * type has its own table.
 * It defines a persistence mechanism that specialized mappers customize by overriding
 * the given template methods.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class NodeRDBMapper extends RDBMapper
{
  /**
   * @see RDBMapper::createObject()
   */
  protected function createObject(ObjectId $oid=null)
  {
    return new Node($this->getType(), $oid);
  }
  /**
   * @see RDBMapper::appendObject()
   */
  protected function appendObject(PersistentObject $object, PersistentObject $dependendObject, $role=null)
  {
    $object->addChild($dependendObject, $role);
  }
  /**
   * @see RDBMapper::applyDataOnLoad()
   */
  protected function applyDataOnLoad(PersistentObject $object, array $objectData, array $attribs)
  {
    // set object data
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc)
    {
      if (sizeof($attribs) == 0 || in_array($curAttributeDesc->name, $attribs))
      {
        $value = $objectData[$curAttributeDesc->name];
        if (!is_null($value)) {
          $value = $this->_dataConverter->convertStorageToApplication($value, $curAttributeDesc->type, $curAttributeDesc->name);
        }
        $object->setValue($curAttributeDesc->name, $value);
      }
    }
  }
  /**
   * @see RDBMapper::applyDataOnCreate()
   */
  protected function applyDataOnCreate(PersistentObject $object, array $attribs)
  {
    // set object data
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc)
    {
      if (sizeof($attribs) == 0 || in_array($curAttributeDesc->name, $attribs))
      {
        // don't override dummy ids
        if (!$this->isPkValue($curAttributeDesc->name))
        {
          $value = $this->_dataConverter->convertStorageToApplication($curAttributeDesc->default, $curAttributeDesc->type, $curAttributeDesc->name);
          $object->setValue($curAttributeDesc->name, $value);
        }
      }
    }
  }
}
?>
