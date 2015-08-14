<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
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
 * AbstractFormat maybe used as base class for specialized formats.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractFormat implements Format {

  private $_deserializedNodes = array();
  private $_request = null;
  private $_response = null;

  /**
   * @see Format::deserialize()
   */
  public function deserialize(Request $request) {
    $this->_request = $request;
    $values = $request->getValues();
    $values = $this->beforeDeserialize($values);
    $values = $this->deserializeValues($values);
    $values = $this->afterDeserialize($values);
    $request->setValues($values);
  }

  /**
   * @see Format::serialize()
   */
  public function serialize(Response $response) {
    $this->_response = $response;
    $values = $response->getValues();
    $values = $this->beforeSerialize($values);
    $values = $this->serializeValues($values);
    $values = $this->afterSerialize($values);
    $response->setValues($values);
  }

  /**
   * Get the currently deserialized request
   * @return Request
   */
  protected function getRequest() {
    return $this->_request;
  }

  /**
   * Get the currently deserialized response
   * @return Response
   */
  protected function getResponse() {
    return $this->_response;
  }

  /**
   * Modify data before deserialization. The default implementation does nothing.
   * @param $values The request values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function beforeDeserialize($values) {
    return $values;
  }

  /**
   * Deserialize an array of values.
   * @param $values The array/object of values
   * @return The array/object of values
   */
  protected abstract function deserializeValues($values);

  /**
   * Modify data after deserialization. The default implementation does nothing.
   * @param $values The request values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function afterDeserialize($values) {
    return $values;
  }

  /**
   * Modify data before serialization. The default implementation does nothing.
   * @param $values The response values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function beforeSerialize($values) {
    return $values;
  }

  /**
   * Serialize an array of values.
   * @param $values The array/object of values
   * @return The array/object of values
   */
  protected abstract function serializeValues($values);

  /**
   * Modify data after serialization. The default implementation does nothing.
   * @param $values The response values
   * @return The modified values array
   * @note Subclasses override this if necessary
   */
  protected function afterSerialize($values) {
    return $values;
  }

  /**
   * Helper methods
   */

  /**
   * Get a node with the given oid to deserialize values from form fields into it.
   * The method ensures that there is only one instance per oid.
   * @param $oid The oid
   * @return A reference to the Node instance
   */
  protected function getNode(ObjectId $oid) {
    $oidStr = $oid->__toString();
    if (!isset($this->_deserializedNodes[$oidStr])) {
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
      $this->_deserializedNodes[$oidStr] = $node;
    }
    return $this->_deserializedNodes[$oidStr];
  }

  protected function filterValue($value, AttributeDescription $attribute) {

  }
}
?>
