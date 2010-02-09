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
 * @class NodeSerializer
 * @ingroup Util
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
  function &deserializeNode($type, $data, $hasFlattendedValues, $parent=null)
  {
    if (PersistenceFacade::isKnownType($type))
    {
      $persistenceFacade = &PersistenceFacade::getInstance();
      // don't create all values by default (-> don't use PersistenceFacade::create())
      $node = new Node($type);
      if ($parent != null)
        $parent->addChild($node);

        if (!$hasFlattendedValues)
      {
        if (is_object($data))
          $dataValues = $data->values;
        else
          $dataValues = $data['values'];
        foreach ($dataValues as $dataType => $values)
        {
          foreach ($values as $key => $value)
            NodeSerializer::deserializeValue($node, $key, $value, $dataType, $hasFlattendedValues);
        }
      }
      else
      {
        // in case of not flattened values, the array only contains
        // value names and values (no data types)
        foreach($data as $key => $value)
          NodeSerializer::deserializeValue($node, $key, $value, null, $hasFlattendedValues);
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
   * @param dataType The dataType of the value
   * @param hasFlattendedValues
   */
  function deserializeValue(&$node, $key, $value, $dataType, $hasFlattendedValues)
  {
    if (!is_array($value)) {
      Log::error("setValue: ".$key."=".$value, __CLASS__);
      $node->setValue($key, $value, $dataType);
    }
    else
    {
      // deserialize children
      foreach($value as $childData)
        NodeSerializer::deserializeNode($key, $childData, $hasFlattendedValues, $node);
    }
  }
  /**
   * Serialize a Node into an array
   * @param obj A reference to the node to serialize
   * @param flattenValues True if all node data should be serialized into one array, false if
   *                      there should be an extra array 'values', that holds the data types and inside these the values
   * @return The node serialized into an associated array
   */
  function serializeNode(&$obj, $flattenValues)
  {
    $result = array();
    $rightsManager = &RightsManager::getInstance();
    
    $iter = new NodeIterator($obj);
    while (!$iter->isEnd())
    {
      $curNode = &$iter->getCurrentObject();
      $curResult = &NodeSerializer::getArray();;
      
      // use NodeProcessor to iterate over all Node values 
      // and call the global convert function on each
      $values = &NodeSerializer::getArray();
      $processor = new NodeProcessor('serializeAttribute', array(&$values, $flattenValues), new NodeSerializer());
      $processor->run($curNode, false);
      
      if ($flattenValues)
        $curResult = $values;
      else
        $curResult['values'] = $values;
      
      // add oid, type, parentoids, childoids
      $curResult['oid'] = $curNode->getOID();
      $curResult['type'] = $curNode->getType();
      $curResult['properties'] = array();
      foreach($curNode->getPropertyNames() as $name)
        $curResult['properties'][$name] = $curNode->getProperty($name);
      
      // add current result to result
      $path = split('/', $curNode->getPath());
      if (sizeof($path) == 1)
      {
        $result = &$curResult;
      }
      else
      {
        array_shift($path);
        $array = &NodeSerializer::getPathArray($result, $path, 0);
        $array[sizeof($array)] = $curResult;
      }
      $iter->proceed();
    }
    
    return $result;
  }
  /**
   * Callback function for NodeProcessor (see NodeProcessor).
   */
  function serializeAttribute(&$node, $valueName, $dataType, &$result, $flattenDataTypes)
  {
    if (!$flattenDataTypes)
    {
      if (!array_key_exists($dataType, $result))
        $result[$dataType] = array();
      $result[$dataType][$valueName] = $node->getValue($valueName, $dataType);
    }
    else
      $result[$valueName] = $node->getValue($valueName, $dataType);
  }
  /**
   */
  function &getArray()
  {
    return array();
  }
  /**
   */
  function &getPathArray(&$array, $path, $curDepth)
  {
    if (!array_key_exists($path[$curDepth], $array))
      $array[$path[$curDepth]] = array();

    if ($curDepth < sizeof($path)-1)
      return NodeSerializer::getPathArray($array[$path[$curDepth]][0], $path, ++$curDepth);
    else
      return $array[$path[$curDepth]];
  }
}
?>
