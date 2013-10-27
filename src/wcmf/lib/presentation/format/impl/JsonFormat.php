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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\core\Log;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\presentation\format\impl\HierarchicalFormat;

/**
 * JsonFormat realizes the JSON request/response format. All data will
 * be serialized using the json_encode method except for Nodes.
 * Nodes are serialized into an array before encoding (see JsonFormat::serializeValue)
 * using the NodeSerializer class.
 * On serialization the data will be outputted directly using the print command.
 *
 * JsonFormat collects the response data from all executed controllers
 * into one response array and returns it all at once at the end of
 * script execution. This prevents from having multiple junks of json
 * from each controller response that can't be decoded by clients.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JsonFormat extends HierarchicalFormat {

  private static $_jsonData = array();
  private static $_jsonUsed = false;

  protected $_serializer = null;

  public static function printJSONResult() {
    if (self::$_jsonUsed) {
      $data = self::$_jsonData;
      if ($data != null) {
        $encoded = json_encode($data);
        if (Log::isDebugEnabled('JsonFormat')) {
          Log::debug($data, 'JsonFormat');
          Log::debug($encoded, 'JsonFormat');
        }
        if (!headers_sent()) {
          header("Content-Type: application/json; charset=utf-8");
          print($encoded);
        }
      }
    }
  }

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'application/json';
  }

  /**
   * Set the NodeSerializer instance to use
   * @param serializer NodeSerializer
   */
  public function setSerializer(NodeSerializer $serializer) {
    $this->_serializer = $serializer;
  }

  /**
   * @see HierarchicalFormat::afterSerialize()
   */
  protected function afterSerialize(array $values) {
    // TODO: check if merging is required for multiple actions
    /*
    // merge data into global data array
    // new values override old
    self::$_jsonData = array_merge(self::$_jsonData, $data);
     */
    self::$_jsonData = $values;
    self::$_jsonUsed = true;
    return $values;
  }

  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  protected function isSerializedNode($value) {
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    return $this->_serializer->isSerializedNode($value);
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($value) {
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    $node = $this->_serializer->serializeNode($value);
    return $node;
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($value) {
    if ($this->_serializer == null) {
      throw new ConfigurationException("The serializer is not set.");
    }
    $result = $this->_serializer->deserializeNode($value);
    return $result;
  }
}

// register the output method
register_shutdown_function(array(__NAMESPACE__.'\JsonFormat', 'printJSONResult'));
?>
