<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\AttributeDescription;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\format\Format;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * AbstractFormat is used as base class for specialized formats.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractFormat implements Format {

  private $headersSent = false;
  private $deserializedNodes = [];

  /**
   * @see Format::deserialize()
   */
  public function deserialize(Request $request) {
    $request->setValues($this->beforeDeserialize($request));
    $request->setValues($this->deserializeValues($request));
    $request->setValues($this->afterDeserialize($request));
  }

  /**
   * @see Format::serialize()
   */
  public function serialize(Response $response) {
    $this->headersSent = headers_sent();
    $response->setValues($this->beforeSerialize($response));
    $this->sendHeaders($response);
    $response->setValues($this->serializeValues($response));
    $response->setValues($this->afterSerialize($response));
  }

  /**
   * Modify data before deserialization. The default implementation does nothing.
   * @param $request The request
   * @return Array/object of values
   * @note Subclasses override this if necessary
   */
  protected function beforeDeserialize(Request $request) {
    return $request->getValues();
  }

  /**
   * Deserialize an array of values.
   * @param $request The request
   * @return Array/object of values
   */
  protected abstract function deserializeValues(Request $request);

  /**
   * Modify data after deserialization. The default implementation does nothing.
   * @param $request The request
   * @return Array/object of values
   * @note Subclasses override this if necessary
   */
  protected function afterDeserialize(Request $request) {
    return $request->getValues();
  }

  /**
   * Modify data before serialization. The default implementation does nothing.
   * @param $response The response
   * @return Array/object of values
   * @note Subclasses override this if necessary
   */
  protected function beforeSerialize(Response $response) {
    return $response->getValues();
  }

  /**
   * Send the response headers.
   * @param $response The response
   */
  protected function sendHeaders(Response $response) {
    $this->sendHeader("Content-Type: ".$this->getMimeType()."; charset=utf-8");
    foreach ($response->getHeaders() as $name => $value) {
      $this->sendHeader($name.": ".$value);
    }
  }

  /**
   * Serialize an array of values.
   * @param $response The response
   * @return Array/object of values
   */
  protected abstract function serializeValues(Response $response);

  /**
   * Modify data after serialization. The default implementation does nothing.
   * @param $response The response
   * @return Array/object of values
   * @note Subclasses override this if necessary
   */
  protected function afterSerialize(Response $response) {
    return $response->getValues();
  }

  /**
   * Helper methods
   */

  /**
   * Get a node with the given oid to deserialize values from form fields into it.
   * The method ensures that there is only one instance per oid.
   * @param $oid The oid
   * @return Node
   */
  protected function getNode(ObjectId $oid) {
    $oidStr = $oid->__toString();
    if (!isset($this->deserializedNodes[$oidStr])) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $type = $oid->getType();
      if ($persistenceFacade->isKnownType($type)) {
        // don't create all values by default (-> don't use PersistenceFacade::create() directly, just for determining the class)
        $class = get_class($persistenceFacade->create($type, BuildDepth::SINGLE));
        $node = new $class;
      }
      else {
        $node = new Node($type);
      }
      $node->setOID($oid);
      $this->deserializedNodes[$oidStr] = $node;
    }
    return $this->deserializedNodes[$oidStr];
  }

  /**
   * Send the given header
   * @param $header
   */
  protected function sendHeader($header) {
    if (!$this->headersSent) {
      header($header);
    }
  }

  protected function filterValue($value, AttributeDescription $attribute) {
    // TODO: implement filtering by attribute type
  }
}
?>
