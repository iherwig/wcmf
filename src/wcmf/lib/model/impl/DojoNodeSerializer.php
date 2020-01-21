<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
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
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObjectProxy;

/**
 * DojoNodeSerializer is used to serialize Nodes into the Dojo rest format and
 * vice versa. The format of serialized Nodes is defined in the Dojo documentation (See:
 * http://dojotoolkit.org/reference-guide/1.10/quickstart/rest.html)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DojoNodeSerializer extends AbstractNodeSerializer {

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

    // create request node
    $node = $this->getNodeTemplate($oid);
    $remainingData = [];
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

    return ['node' => $node, 'data' => $remainingData];
  }

  /**
   * @see NodeSerializer::serializeNode
   */
  public function serializeNode($node, $rolesToRefOnly=[], $serializedOids=[]) {
    if (!($node instanceof Node)) {
      return null;
    }
    $oid = $node->getOID()->__toString();
    if (!in_array($oid, $serializedOids)) {
      $curResult = [];
      $curResult['oid'] = $oid;

      // serialize attributes
      // use NodeValueIterator to iterate over all Node values
      $valueIter = new NodeValueIterator($node, false);
      foreach($valueIter as $valueName => $value) {
        $curResult[$valueName] = $value;
      }

      $serializedOids[] = $curResult['oid'];

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
            $curResult[$role] = [];
            foreach ($relatedNodes as $relatedNode) {
              if ($relatedNode instanceof PersistentObjectProxy || in_array($role, $rolesToRefOnly)) {
                // add the reference to the relation attribute
                $curResult[$role][] = ['$ref' => $relatedNode->getOID()->__toString()];
              }
              else {
                $curResult[$role][] = $this->serializeNode($relatedNode, [$relation->getThisRole()], $serializedOids);
              }
            }
          }
          else {
            $relatedNode = $relatedNodes;
            if ($relatedNode instanceof PersistentObjectProxy || in_array($role, $rolesToRefOnly)) {
              // add the reference to the relation attribute
              $curResult[$role] = ['$ref' => $relatedNode->getOID()->__toString()];
            }
            else {
              $curResult[$role] = $this->serializeNode($relatedNode, [$relation->getThisRole()], $serializedOids);
            }
          }
        }
      }
    }
    else {
      // only add a reference for already serialized nodes to prevent recursion
      $curResult = ['ref' => $oid];
    }
    return $curResult;
  }
}
?>
