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
namespace wcmf\lib\model\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\model\impl\AbstractNodeSerializer;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObjectProxy;

/**
 * DojoNodeSerializer is used to serialize Nodes into the Dojo rest format and
 * vice versa. The format of serialized Nodes is defined in the Dojo documentation (See:
 * http://dojotoolkit.org/reference-guide/1.10/quickstart/rest.html)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DojoNodeSerializer extends AbstractNodeSerializer {

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

    $mapper = $node->getMapper();
    foreach($data as $key => $value) {
      if ($mapper->hasAttribute($key)) {
        $this->deserializeValue($node, $key, $value);
      }
      else {
        $remainingData[$key] = $value;
      }
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
  public function serializeNode($node, $rolesToRefOnly=array()) {
    if (!($node instanceof Node)) {
      return null;
    }
    $curResult = array();
    $curResult['oid'] = $node->getOID()->__toString();

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
        $isMultiValued = $relation->isMultiValued();
        if ($isMultiValued) {
          $curResult[$role] = array();
          foreach ($relatedNodes as $relatedNode) {
            if ($relatedNode instanceof PersistentObjectProxy || in_array($role, $rolesToRefOnly)) {
              // add the reference to the relation attribute
              $curResult[$role][] = array('$ref' => $relatedNode->getOID()->__toString());
            }
            else {
              $curResult[$role][] = $this->serializeNode($relatedNode, array($relation->getThisRole()));
            }
          }
        }
        else {
          $relatedNode = $relatedNodes;
          if ($relatedNode instanceof PersistentObjectProxy || in_array($role, $rolesToRefOnly)) {
            // add the reference to the relation attribute
            $curResult[$role] = array('$ref' => $relatedNode->getOID()->__toString());
          }
          else {
            $curResult[$role] = $this->serializeNode($relatedNode, array($relation->getThisRole()));
          }
        }
      }
    }
    return $curResult;
  }
}
?>
