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

use wcmf\lib\core\LogManager;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\presentation\format\impl\HierarchicalFormat;
use wcmf\lib\presentation\Response;

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

  private static $jsonData = array();
  private static $jsonUsed = false;
  private static $logger = null;

  protected $serializer = null;

  /**
   * Constructor
   * @param $serializer NodeSerializer instance
   */
  public function __construct(NodeSerializer $serializer) {
    $this->serializer = $serializer;
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
  }

  public static function printJSONResult() {
    if (self::$jsonUsed) {
      $data = self::$jsonData;
      if ($data !== null) {
        $encoded = json_encode($data);
        if (self::$logger->isDebugEnabled(__CLASS__)) {
          self::$logger->debug($data);
          self::$logger->debug($encoded);
        }
        print($encoded);
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
   * @see Format::isCached()
   */
  public function isCached(Response $response) {
    return false;
  }

  /**
   * @see HierarchicalFormat::afterSerialize()
   */
  protected function afterSerialize(Response $response) {
    // TODO: check if merging is required for multiple actions
    /*
    // merge data into global data array
    // new values override old
    self::$jsonData = array_merge(self::$jsonData, $data);
     */
    self::$jsonData = $response->getValues();
    self::$jsonUsed = true;
    return self::$jsonData;
  }

  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  protected function isSerializedNode($value) {
    return $this->serializer->isSerializedNode($value);
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($value) {
    $node = $this->serializer->serializeNode($value);
    return $node;
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($value) {
    $result = $this->serializer->deserializeNode($value);
    return $result;
  }
}

// register the output method
register_shutdown_function(array(__NAMESPACE__.'\JsonFormat', 'printJSONResult'));
?>
