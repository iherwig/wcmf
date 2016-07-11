<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\model\Node;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\format\impl\AbstractFormat;

/**
 * HierarchicalFormat maybe used as base class for formats that
 * are able to represent hierarchical data like JSON or XML. This format
 * automatically iterates over data when de-/serializing and uses template
 * methods to implement the specific format.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class HierarchicalFormat extends AbstractFormat {

  /**
   * @see AbstractFormat::deserializeValues()
   */
  protected function deserializeValues($values) {
    if ($this->isSerializedNode($values)) {
      // the values represent a node
      $result = $this->deserializeNode($values);
      $node = $result['node'];
      $values = $result['data'];
      $values[$node->getOID()->__toString()] = $node;
    }
    else {
      foreach ($values as $key => $value) {
        if (is_array($value) || is_object($value)) {
          // array/object value
          $result = $this->deserializeValues($value);
          // flatten the array, if the deserialization result is only an array
          // with size 1 and the key is an oid (e.g. if a node was deserialized)
          if (is_array($result) && sizeof($result) == 1 && ObjectId::isValid(key($result))) {
            unset($values[$key]);
            $values[key($result)] = current($result);
          }
          else {
            $values[$key] = $result;
          }
        }
        else {
          // string value
          $values[$key] = $value;
        }
      }
    }
    return $values;
  }

  /**
   * @see AbstractFormat::serializeValues()
   */
  protected function serializeValues($values) {
    if ($this->isDeserializedNode($values)) {
      // the values represent a node
      $values = $this->serializeNode($values);
    }
    else {
      foreach ($values as $key => $value) {
        if (is_array($value) || is_object($value)) {
          // array/object value
          $result = $this->serializeValues($value);
          if (ObjectId::isValid($key)) {
            $values = $result;
          }
          else {
            $values[$key] = $result;
          }
        }
        else {
          // string value
          $values[$key] = $value;
        }
      }
    }
    return $values;
  }

  /**
   * Determine if the value is a serialized Node. The default
   * implementation returns false.
   * @param $value The data value
   * @return Boolean
   * @note Subclasses override this if necessary
   */
  protected function isSerializedNode($value) {
    return false;
  }

  /**
   * Determine if the value is a deserialized Node. The default
   * implementation checks if the value is an object of type Node.
   * @param $value The data value
   * @return Boolean
   * @note Subclasses override this if necessary
   */
  protected function isDeserializedNode($value) {
    return ($value instanceof Node);
  }

  /**
   * Serialize a Node
   * @param $value The data value
   * @return The serialized Node
   */
  protected abstract function serializeNode($value);

  /**
   * Deserialize a Node
   * @param $value The data value
   * @return An array with keys 'node' and 'data' where the node
   * value is the Node instance and the data value is the
   * remaining part of data, that is not used for deserializing the Node
   */
  protected abstract function deserializeNode($value);
}
?>
