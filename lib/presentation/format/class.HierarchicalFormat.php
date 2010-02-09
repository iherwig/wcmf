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
require_once(BASE."wcmf/lib/presentation/format/class.AbstractFormat.php");

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
  public function deserialize(&$request)
  {
    $data = &$request->getData();

    // deserialize Nodes
    $this->beforeDeserialize($data);
    ArrayUtil::array_walk_recursive($data, array($this, 'processValues'), 'deserializeNode');
    $this->afterDeserialize($data);
  }
  /**
   * @see IFormat::serialize()
   */
  public function serialize($response)
  {
    $data = &$response->getData();

    // serialize Nodes
    $this->beforeSerialize($data);
    ArrayUtil::array_walk_recursive($data, array($this, 'processValues'), 'serializeNode');
    $this->afterSerialize($data);
  }
  /**
   * Callback function for array_walk_recursive. De-/Serializes any Node instances
   * using the function given in method parameter.
   * @param value The array value
   * @param key The array key
   * @param method The method to apply to each value
   */
  protected function processValues(&$value, $key, $method)
  {
    if (EncodingUtil::isUtf8($value)) {
      $value = EncodingUtil::convertCp1252Utf8ToIso($value);
    }
    if ( (strpos($method, 'deserialize') === 0 && $this->isSerializedNode($key, $value)) ||
      (strpos($method, 'serialize') === 0 && $this->isDeserializedNode($key, $value)) )
    {
      $value = $this->$method($key, $value);
    }
  }

  /**
   * Template methods
   */

  /**
   * Modify data before deserialization. The default implementation does nothing.
   * @param data A reference to the data array
   * @note Subclasses override this if necessary
   */
  protected function beforeDeserialize(&$data) {}
  /**
   * Modify data after deserialization. The default implementation does nothing.
   * @param data A reference to the data array
   * @note Subclasses override this if necessary
   */
  protected function afterDeserialize(&$data) {}

  /**
   * Modify data before serialization. The default implementation does nothing.
   * @param data A reference to the data array
   * @note Subclasses override this if necessary
   */
  protected function beforeSerialize(&$data) {}
  /**
   * Modify data after serialization. The default implementation does nothing.
   * @param data A reference to the data array
   * @note Subclasses override this if necessary
   */
  protected function afterSerialize(&$data) {}

  /**
   * Determine if the value is a serialized Node. The default
   * implementation returns false.
   * @param key The data key
   * @param value A reference to the data value
   * @return True/False
   * @note Subclasses override this if necessary
   */
  protected function isSerializedNode($key, &$value)
  {
    return false;
  }
  /**
   * Determine if the value is a deserialized Node. The default
   * implementation checks if the value is an object of type Node.
   * @param key The data key
   * @param value A reference to the data value
   * @return True/False
   * @note Subclasses override this if necessary
   */
  protected function isDeserializedNode($key, &$value)
  {
    return ($value instanceof Node);
  }

  /**
   * Serialize a Node
   * @param key The data key
   * @param value A reference to the data value
   * @return The serialized Node
   */
  protected abstract function serializeNode($key, &$value);

  /**
   * Deserialize a Node
   * @param key The data key
   * @param value A reference to the data value
   * @return The deserialized Node
   */
  protected abstract function deserializeNode($key, &$value);
}
?>
