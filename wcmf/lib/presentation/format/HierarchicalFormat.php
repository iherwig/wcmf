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
require_once(WCMF_BASE."wcmf/lib/presentation/format/AbstractFormat.php");

/**
 * @class HierarchicalFormat
 * @ingroup Format
 * @brief HierarchicalFormat maybe used as base class for formats that
 * are able to represent hierarchical data like JSON or XML. This format
 * automatically iterates over data when de-/serializing and uses template
 * methods to implement the specific format.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class HierarchicalFormat extends AbstractFormat
{
  /**
   * @see IFormat::deserialize()
   */
  public function deserialize(Request $request)
  {
    $values = $request->getValues();
    $values = $this->beforeDeserialize($values);
    $values = $this->deserializeValues($values);
    $values = $this->afterDeserialize($values);
    $request->setValues($values);
  }
  /**
   * @see IFormat::serialize()
   */
  public function serialize(Response $response)
  {
    $values = $response->getValues();
    $values = $this->beforeSerialize($values);
    $values = $this->serializeValues($values);
    $values = $this->afterSerialize($values);
    $response->setValues($values);
  }
  /**
   * Deserialize an array of values.
   * @param values The array/object of values
   *
   */
  protected function deserializeValues($values)
  {
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
          // with size 1 (e.g. if a node was deserialized)
          if (is_array($result) && sizeof($result) == 1) {
            $values[key($result)] = current($result);
            unset($values[$key]);
          }
          else {
            $values[$key] = $result;
          }
        }
        else {
          // string value
          if (is_string($value) && EncodingUtil::isUtf8($value)) {
            $values[$key] = EncodingUtil::convertCp1252Utf8ToIso($value);
          }
        }
      }
    }
    return $values;
  }
  /**
   * Serialize an array of values.
   * @param values The array/object of values
   */
  protected function serializeValues($values)
  {
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
          if (is_string($value) && EncodingUtil::isUtf8($value)) {
            $values[$key] = EncodingUtil::convertCp1252Utf8ToIso($value);
          }
        }
      }
    }
    return $values;
}

  /**
   * Template methods
   */

  /**
   * Modify data before deserialization. The default implementation does nothing.
   * @param values The request values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function beforeDeserialize(array $values)
  {
    return $values;
  }
  /**
   * Modify data after deserialization. The default implementation does nothing.
   * @param values The request values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function afterDeserialize(array $values)
  {
    return $values;
  }
  /**
   * Modify data before serialization. The default implementation does nothing.
   * @param values The response values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function beforeSerialize(array $values)
  {
    return $values;
  }
  /**
   * Modify data after serialization. The default implementation does nothing.
   * @param values The response values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function afterSerialize(array $values)
  {
    return $values;
  }

  /**
   * Determine if the value is a serialized Node. The default
   * implementation returns false.
   * @param value The data value
   * @return True/False
   * @note Subclasses override this if necessary
   */
  protected function isSerializedNode($value)
  {
    return false;
  }
  /**
   * Determine if the value is a deserialized Node. The default
   * implementation checks if the value is an object of type Node.
   * @param value The data value
   * @return True/False
   * @note Subclasses override this if necessary
   */
  protected function isDeserializedNode($value)
  {
    return ($value instanceof Node);
  }

  /**
   * Serialize a Node
   * @param value The data value
   * @return The serialized Node
   */
  protected abstract function serializeNode($value);

  /**
   * Deserialize a Node
   * @param value The data value
   * @return An array with keys 'node' and 'data' where the node
   * value is the Node instance and the data value is the
   * remaining part of data, that is not used for deserializing the Node
   */
  protected abstract function deserializeNode($value);
}
?>
