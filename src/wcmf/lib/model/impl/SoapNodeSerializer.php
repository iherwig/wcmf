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
namespace wcmf\lib\model\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\model\impl\AbstractNodeSerializer;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;

/**
 * SoapNodeSerializer is used to serialize Nodes into the soap format and
 * vice versa. The format of serialized Nodes is defined by the wCMF soap server.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapNodeSerializer extends AbstractNodeSerializer {

  private $serializedOIDs = array();
  private $persistenceFacade = null;

  /**
   * Constructor
   * @param $persistenceFacade
   */
  public function __construct(PersistenceFacade $persistenceFacade) {
    $this->persistenceFacade = $persistenceFacade;
  }

  /**
   * @see NodeSerializer::isSerializedNode
   */
  public function isSerializedNode($data) {
    if (is_object($data)) {
      $data = (array)$data;
    }
    $syntaxOk = (is_array($data) && (isset($data['oid'])));
    // check for oid variables
    if ($syntaxOk && isset($data['oid']) && preg_match('/^\{.+\}$/', $data['oid'])) {
      $syntaxOk = false;
    }
    return $syntaxOk;
  }

  /**
   * @see NodeSerializer::deserializeNode
   */
  public function deserializeNode($data, Node $parent=null, $role=null) {
    if (!isset($data['oid'])) {
      throw new IllegalArgumentException("Serialized Node data must contain an 'oid' parameter");
    }
    $oid = ObjectId::parse($data['oid']);
    if ($oid == null) {
      throw new IllegalArgumentException("The object id '".$oid."' is invalid");
    }

    // don't create all values by default (-> don't use PersistenceFacade::create() directly,
    // just for determining the class)
    $class = get_class($this->persistenceFacade->create($oid->getType(), BuildDepth::SINGLE));
    $node = new $class;

    $remainingData = array();

    $foundNodeAttribute = false;
    $mapper = $node->getMapper();
    foreach($data as $key => $value) {
      if ($mapper->hasAttribute($key) || $mapper->hasRelation($key)) {
        $this->deserializeValue($node, $key, $value);
        $foundNodeAttribute = true;
      }
      else {
        $remainingData[$key] = $value;
      }
    }

    if ($foundNodeAttribute) {
      // a node was deserialized -> remove oid from remaining data
      unset($remainingData['oid']);
    }

    // set oid after attributes in order to
    // avoid it being changed from missing pk values
    $node->setOID($oid);

    // create hierarchy
    if ($parent != null) {
      $parent->addNode($node, $role);
    }

    return array('node' => $node, 'data' => $remainingData);
  }

  /**
   * @see NodeSerializer::serializeNode
   */
  public function serializeNode($node) {
    if (!($node instanceof Node)) {
      return null;
    }
    $curResult = array();
    $oidStr = $node->getOID()->__toString();
    $curResult['oid'] = $oidStr;

    if (!in_array($oidStr, $this->serializedOIDs)) {
      $this->serializedOIDs[] = $oidStr;

      // serialize attributes
      // use NodeValueIterator to iterate over all Node values
      $valueIter = new NodeValueIterator($node, false);
      foreach($valueIter as $valueName => $value) {
        $curResult[$valueName] = $value;
      }

      // add related objects by creating an attribute that is named as the role of the object
      // multivalued relations will be serialized into an array
      $mapper = $node->getMapper();
      foreach ($mapper->getRelations() as $relation) {
        $role = $relation->getOtherRole();
        $relatedNodes = $node->getValue($role);
        if ($relatedNodes) {
          // serialize the nodes
          $curResult[$role] = array();
          $isMultiValued = $relation->isMultiValued();
          if ($isMultiValued) {
            foreach ($relatedNodes as $relatedNode) {
              // add serialized node
              $curResult[$role][] = $this->serializeNode($relatedNode);
            }
          }
          else {
              // add serialized node
            $relatedNode = $relatedNodes;
            $curResult[$role][] = $this->serializeNode($relatedNode);
          }
        }
      }
    }
    return $curResult;
  }
}
?>
