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
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(BASE."wcmf/lib/model/class.NodeProcessor.php");

/**
 * @class DionysosNodeSerializer
 * @ingroup Util
 * @brief DionysosNodeSerializer provides helper functions to de-/serialize Nodes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DionysosNodeSerializer
{
  /**
   * Deserialize a Node from serialized data. Only values given in data are be set.
   * @param type The type the data belong to
   * @param data The serialized node data (either as object or as array)
   * @param parent The parent node [default: null]
   * @return A reference to the node deserialized from the data or null if the type does not exist
   */
  function &deserializeNode($type, $data, $parent=null)
  {
    if (PersistenceFacade::isKnownType($type))
    {
      $persistenceFacade = &PersistenceFacade::getInstance();
      // don't create all values by default (-> don't use PersistenceFacade::create())
      $node = new Node($type);
      if ($parent != null) {
        $parent->addChild($node);
      }
      $template = &$persistenceFacade->create($type, BUILDDEPTH_SINGLE);
      foreach($data['attributes'] as $key => $value) {
        if (!$template->hasValue($key)) {
          throw new DionysosException(null, null, 'The attribute id '.$key.' is unknown in '.$type, DionysosException::ATTRIBUTE_NAME_INVALID);
        }
        DionysosNodeSerializer::deserializeValue($node, $key, $value);
      }
      return $node;
    }
    else
      return null;
  }
  /**
   * Deserialize an node value
   * @param node A reference to the node
   * @param key The value name or type if value is an array
   * @param value The value or child data, if value is an array
   */
  function deserializeValue(&$node, $key, $value)
  {
    if (!is_array($value)) {
      // since the node is not created by PersistenceFacade::create, the values
      // are unknown. so we have to guess the datatype
      $node->setValue($key, $value, DATATYPE_ATTRIBUTE);
    }
    else
    {
      // deserialize children
      foreach($value as $childData) {
        DionysosNodeSerializer::deserializeNode($key, $childData, $node);
      }
    }
  }
  /**
   * Serialize a Node into an array
   * @param obj A reference to the node to serialize
   * @return The node serialized into an associated array
   */
  function serializeNode(&$obj)
  {
    $result = array();
    $rightsManager = &RightsManager::getInstance();
    $serializedOids = array();

    $iter = new NodeIterator($obj);
    while (!$iter->isEnd())
    {
      $curNode = &$iter->getCurrentObject();
      $curResult = &DionysosNodeSerializer::getArray();

      // add className, oid, isReference, lastChange
      $curResult['className'] = $curNode->getBaseType();
      $curResult['oid'] = $curNode->getBaseOID();
      $curResult['isReference'] = false;
      $curResult['lastChange'] = strtotime($curNode->getValue('modified', DATATYPE_ATTRIBUTE));

      // use NodeProcessor to iterate over all Node values
      // and call the global convert function on each
      $values = &DionysosNodeSerializer::getArray();
      $processor = new NodeProcessor('serializeAttribute', array(&$values), new DionysosNodeSerializer());
      $processor->run($curNode, false);
      $curResult['attributes'] = $values;
      $serializedOids[] = $curNode->getOID();

      // resolve parentoids as references (they are all loaded and serialized already)
      $parentOIDs = $curNode->getProperty('parentoids');
      foreach($parentOIDs as $oid)
      {
        $ref = DionysosNodeSerializer::serializeAsReference($oid);
        if ($ref != null)
        {
          // references to parents are single valued
          $type = $ref['type'];
          $nodeRef = array('className' => $ref['baseType'], 'oid' => $ref['baseOID'], 'isReference' => true);
          $curResult['attributes'][$type] = $nodeRef;
        }
      }

      // resolve childoids as references (if they are not loaded)
      $childOIDs = $curNode->getProperty('childoids');
      $children = $curNode->getChildren();
      foreach($childOIDs as $oid)
      {
        // add only if no child object with this oid is loaded and will be serialized as full object later
        $ignoreChild = false;
        $child = &PersistenceFacade::getInstance()->create(PersistenceFacade::getOIDParameter($oid, 'type'), BUILDDEPTH_SINGLE);
        if (in_array($oid, $serializedOids) || $child->isManyToManyObject()) {
          $ignoreChild = true;
        }
        else
        {
          foreach($children as $child)
          {
            if ($child->getOID() == $oid) {
              $ignoreChild = true;
              break;
            }
          }
        }
        if (!$ignoreChild)
        {
          $ref = DionysosNodeSerializer::serializeAsReference($oid, $curNode->getType());
          if ($ref != null)
          {
            $type = $ref['type'];
            $nodeRef = array('className' => $ref['baseType'], 'oid' => $ref['baseOID'], 'isReference' => true);
            if ($ref['isMultiValued'])
            {
              if (!isset($curResult['attributes'][$type])) {
                $curResult['attributes'][$type] = array();
              }
              $curResult['attributes'][$type][] = $nodeRef;
            }
            else {
              $curResult['attributes'][$type] = $nodeRef;
            }
          }
        }
      }

      // add current result to result
      $path = split('/', $curNode->getPath());
      if (sizeof($path) == 1)
      {
        $result = &$curResult;
      }
      else
      {
        $isMultiValued = DionysosNodeSerializer::isMultiValued($path[0], $path[1]);
        $array = &DionysosNodeSerializer::getPathArray($result, $path, 1);
        if ($isMultiValued) {
          $array[] = $curResult;
        }
        else {
          $array = $curResult;
        }
      }
      $iter->proceed();
    }

    return $result;
  }
  /**
   * Callback function for NodeProcessor (see NodeProcessor).
   */
  function serializeAttribute(&$node, $valueName, $dataType, &$result)
  {
    $result[$valueName] = $node->getValue($valueName, $dataType);
  }
  /**
   * Serialize a oid as a reference.
   * @param oid The oid
   * @param parentType The parent node type (optional, default: null)
   * @return An associative array with keys 'baseType', 'baseOID', 'type', 'isMultiValued' or null,
   * if the oid is a dummy id
   */
  function serializeAsReference($oid, $parentType=null)
  {
    $oidParts = PersistenceFacade::decomposeOID($oid);
    if (!PersistenceFacade::isDummyId(join('', $oidParts['id'])))
    {
      $type = $oidParts['type'];
      $persistenceFacade = &PersistenceFacade::getInstance();
      $relativeNode = &$persistenceFacade->create($type, BUILDDEPTH_SINGLE);
      $baseType = $relativeNode->getBaseType();
      $baseOID = PersistenceFacade::composeOID(array('type' => $baseType, 'id' => $oidParts['id']));

      $isMultiValued = false;
      if ($parentType) {
        $isMultiValued = DionysosNodeSerializer::isMultiValued($parentType, $type);
      }
      return array('baseType' => $baseType, 'baseOID' => $baseOID, 'type' => $type, 'isMultiValued' => $isMultiValued);
    }
    else {
      return null;
    }
  }
  function isMultiValued($parentType, $childType)
  {
    $isMultiValued = false;
    $persistenceFacade = &PersistenceFacade::getInstance();
    $parentNode = &$persistenceFacade->create($parentType, BUILDDEPTH_SINGLE);
    if ($parentNode)
    {
      $mapper = $parentNode->getMapper();
      $objectData = $mapper->getObjectDefinition();
      $found = false;
      foreach ($objectData['_children'] as $childData)
      {
        if ($childData['type'] == $childType) {
          $found = true;
          if ($childData['maxOccurs'] > 1 || $childData['maxOccurs'] == 'unbounded') {
            $isMultiValued = true;
          }
        }
      }
      if (!$found) {
        // fallback: assume that the connetion is established by a many to many object
        $isMultiValued = true;
      }
    }
    return $isMultiValued;
  }
  /**
   * Get the array, to which an object with the given path should be added.
   * @param array A reference to the current result array
   * @param path An array of path elements, describing the location of the object to add
   * @param curDepth The depth to start searching for
   * @return A reference to the array to which the object should be added
   */
  function &getPathArray(&$array, $path, $curDepth)
  {
    $isMultiValued = false;
    if ($curDepth > 0) {
      $isMultiValued = DionysosNodeSerializer::isMultiValued($path[$curDepth-1], $path[$curDepth]);
    }

    // if there is no entry for the current type in the attributes array, create it
    if (!isset($array['attributes'][$path[$curDepth]])) {
      $array['attributes'][$path[$curDepth]] = array();
    }
    if ($curDepth < sizeof($path)-1) {
      if ($isMultiValued) {
        return DionysosNodeSerializer::getPathArray($array['attributes'][$path[$curDepth]][0], $path, ++$curDepth);
      }
      else {
        return DionysosNodeSerializer::getPathArray($array['attributes'][$path[$curDepth]], $path, ++$curDepth);
      }
    }
    else {
      return $array['attributes'][$path[$curDepth]];
    }
  }
  /**
   */
  function &getArray()
  {
    return array();
  }
}
?>
