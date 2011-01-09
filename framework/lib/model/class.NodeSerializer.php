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
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeValueIterator.php");

/**
 * @class NodeSerializer
 * @ingroup Model
 * @brief NodeSerializer provides helper functions to de-/serialize Nodes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeSerializer
{
  /**
   * Deserialize a Node from serialized data. Only values given in data are be set.
   * @param type The type the data belong to
   * @param data The serialized node data (either as object or as array)
   * @param hasFlattendedValues True if all node data is serialized into one array, false if
   *                      there is an extra array 'values', that holds the data types and inside these the values
   * @param parent The parent node [default: null]
   * @return A reference to the node deserialized from the data or null if the type does not exist
   */
  public function deserializeNode($type, $data, $hasFlattendedValues, Node $parent=null)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    if ($persistenceFacade->isKnownType($type))
    {
      // don't create all values by default (-> don't use PersistenceFacade::create() directly, just for determining the class)
      $class = get_class($persistenceFacade->create($type, BUILDDEPTH_SINGLE));
      $node = new $class;
      
      foreach($data['attributes'] as $key => $value) {
        self::deserializeValue($node, $key, $value, $hasFlattendedValues);
      }
      if ($parent != null) {
        $parent->addChild($node);
      }
      return $node;
    }
    else {
      return null;
    }
  }
  /**
   * Deserialize an node value
   * @param node A reference to the node
   * @param key The value name or type if value is an array
   * @param value The value or child data, if value is an array
   */
  protected function deserializeValue(Node $node, $key, $value)
  {
    if (!is_array($value)) {
      $node->setValue($key, $value);
    }
    else
    {
      // deserialize children
      foreach($value as $childData) {
        self::deserializeNode($key, $childData, $node);
      }
    }
  }
  /**
   * Serialize a Node into an array
   * @param node A reference to the node to serialize
   * @return The node serialized into an associated array
   */
  public function serializeNode(Node $obj)
  {
    $result = array();
    $rightsManager = RightsManager::getInstance();
    $serializedOids = array();

    $iter = new NodeIterator($obj);
    while (!$iter->isEnd())
    {
      $curNode = $iter->getCurrentNode();
      $curResult = array();

      // add className, oid, isReference, lastChange
      $curResult['className'] = $curNode->getType();
      $curResult['oid'] = $curNode->getOID();
      $curResult['isReference'] = false;
      $curResult['lastChange'] = strtotime($curNode->getValue('modified'));

      // use NodeValueIterator to iterate over all Node values
      $values = array();
      $valueIter = new NodeValueIterator($curNode, false);
      while (!$valueIter->isEnd())
      {
        $curIterNode = $valueIter->getCurrentNode();
        $valueName = $valueIter->getCurrentAttribute();
        $values[$valueName] = $curIterNode->getValue($valueName);
        $valueIter->proceed();            
      }
      $curResult['attributes'] = $values;
      //Log::error($curResult['attributes'], __CLASS__);
      $serializedOids[] = $curNode->getOID();

      // resolve parentoids as references (they are all loaded and serialized already)
      /*
      $parentOIDs = $curNode->getProperty('parentoids');
      foreach($parentOIDs as $oid)
      {
        $ref = self::serializeAsReference($oid);
        if ($ref != null)
        {
          // references to parents are single valued
          $type = $ref['type'];
          $nodeRef = array('className' => $ref['baseType'], 'oid' => $ref['baseOID'], 'isReference' => true);
          $curResult['attributes'][$type] = $nodeRef;
        }
      }*/

      // resolve childoids as references (if they are not loaded)
      /*
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
          $ref = self::serializeAsReference($oid, $curNode->getType());
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
      }*/

      // add current result to result
      $path = preg_split('/\//', $curNode->getPath());
      if (sizeof($path) == 1)
      {
        $result = &$curResult;
      }
      else
      {
        $isMultiValued = self::isMultiValued($path[0], $path[1]);
        $array = &self::getPathArray($result, $path, 1);
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
   * Serialize a oid as a reference.
   * @param oid The oid
   * @param parentType The parent node type (optional, default: null)
   * @return An associative array with keys 'baseType', 'baseOID', 'type', 'isMultiValued' or null,
   * if the oid is a dummy id
   */
  protected function serializeAsReference($oid, $parentType=null)
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
        $isMultiValued = self::isMultiValued($parentType, $type);
      }
      return array('baseType' => $baseType, 'baseOID' => $baseOID, 'type' => $type, 'isMultiValued' => $isMultiValued);
    }
    else {
      return null;
    }
  }
  protected function isMultiValued($parentType, $childType)
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
  protected function getPathArray(&$array, $path, $curDepth)
  {
    $isMultiValued = false;
    if ($curDepth > 0) {
      $isMultiValued = self::isMultiValued($path[$curDepth-1], $path[$curDepth]);
    }

    // if there is no entry for the current type in the attributes array, create it
    if (!isset($array['attributes'][$path[$curDepth]])) {
      $array['attributes'][$path[$curDepth]] = array();
    }
    if ($curDepth < sizeof($path)-1) {
      if ($isMultiValued) {
        return self::getPathArray($array['attributes'][$path[$curDepth]][0], $path, ++$curDepth);
      }
      else {
        return self::getPathArray($array['attributes'][$path[$curDepth]], $path, ++$curDepth);
      }
    }
    else {
      return $array['attributes'][$path[$curDepth]];
    }
  }
}
?>
