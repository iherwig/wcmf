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
require_once(WCMF_BASE."wcmf/lib/model/class.NodeProcessor.php");

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
  public function deserializeNode($type, $data, $hasFlattendedValues, Node $parent=null)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    if ($persistenceFacade->isKnownType($type))
    {
      $node = $persistenceFacade->create($type, BUILDEPTH_SINGLE);
      // remove default values
      $node->clearValues();

      if (!$hasFlattendedValues)
      {
        $valueData = array();
        $properties = array();
        $relatives = array();
        foreach($data as $key => $value)
        {
          if ($key == 'values') {
            $valueData = $value;
          }
          elseif ($key == 'properties') {
            $properties = $value;
          }
          elseif ($key != 'oid' && $key != 'type') {
            $relatives[$key] = $value;
          }
        }
        foreach ($valueData as $key => $value) {
          NodeSerializer::deserializeValue($node, $key, $value, $hasFlattendedValues);
        }
        foreach ($properties as $key => $value) {
          $node->setProperty($key, $value);
        }
        foreach ($relatives as $type => $objects)
        {
          foreach ($objects as $object) {
            self::deserializeNode($type, $object, $hasFlattendedValues, $node);
          }
        }
      }
      else
      {
        // in case of not flattened values, the array only contains
        // value names and values (no data types)
        foreach($data as $key => $value)
          self::deserializeValue($node, $key, $value, $hasFlattendedValues);
      }

      if ($parent != null) {
        $parent->addChild($node);
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
   * @param hasFlattendedValues
   */
  protected function deserializeValue(Node $node, $key, $value, $hasFlattendedValues)
  {
    if (!is_array($value)) {
      $node->setValue($key, $value);
    }
    else
    {
      // deserialize children
      foreach($value as $childData) {
        self::deserializeNode($key, $childData, $hasFlattendedValues, $node);
      }
    }
  }
  /**
   * Serialize a Node into an array
   * @param node A reference to the node to serialize
   * @param flattenValues True if all node data should be serialized into one array, false if
   *                      there should be an extra array 'values', that holds the data types and inside these the values
   * @return The node serialized into an associated array
   */
  public function serializeNode(Node $obj, $flattenValues)
  {
    $result = array();
    $rightsManager = RightsManager::getInstance();

    $iter = new NodeIterator($obj);
    while (!$iter->isEnd())
    {
      $curNode = $iter->getCurrentObject();
      $curResult = array();

      // use NodeProcessor to iterate over all Node values
      // and call the global convert function on each
      $values = array();
      $processor = new NodeProcessor('serializeAttribute', array(&$values, $flattenValues), new NodeSerializer());
      $processor->run($curNode, false);

      if ($flattenValues) {
        $curResult = $values;
      }
      else {
        $curResult['values'] = $values;
      }

      // add oid, type, parentoids, childoids
      $curResult['oid'] = $curNode->getOID();
      $curResult['type'] = $curNode->getType();
      $curResult['properties'] = array();
      foreach($curNode->getPropertyNames() as $name) {
        $curResult['properties'][$name] = $curNode->getProperty($name);
      }
      // add current result to result
      $path = preg_split('/\//', $curNode->getPath());
      if (sizeof($path) == 1) {
        $result = $curResult;
      }
      else
      {
        array_shift($path);
        $array = self::getPathArray($result, $path, 0);
        $array[sizeof($array)] = $curResult;
      }
      $iter->proceed();
    }
    return $result;
  }
  /**
   * Callback function for NodeProcessor (see NodeProcessor).
   */
  protected function serializeAttribute(Node $node, $valueName, &$result)
  {
    $result[$valueName] = $node->getValue($valueName);
  }
  /**
   */
  protected function getPathArray(&$array, $path, $curDepth)
  {
    if (!isset($array[$path[$curDepth]]))
      $array[$path[$curDepth]] = array();

    if ($curDepth < sizeof($path)-1)
      return self::getPathArray($array[$path[$curDepth]][0], $path, ++$curDepth);
    else
      return $array[$path[$curDepth]];
  }
}
?>
