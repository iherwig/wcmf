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
namespace wcmf\lib\model\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;

/**
 * DojoNodeSerializer is used to serialize Nodes into the Dojo rest format and
 * vice versa. The format of serialized Nodes is defined in the Dojo documentation (See:
 * http://dojotoolkit.org/reference-guide/1.8/quickstart/rest.html)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DojoNodeSerializer implements NodeSerializer {

  private $_serializedOIDs = array();

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
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $class = get_class($persistenceFacade->create($oid->getType(), BuildDepth::SINGLE));
    $node = new $class;
    $node->setOID($oid);

    $mapper = $node->getMapper();
    foreach($data as $key => $value) {
      if ($mapper->hasAttribute($key)) {
        $this->deserializeValue($node, $key, $value);
      }
    }
    if ($parent != null) {
      $parent->addNode($node, $role);
    }

    return array('node' => $node, 'data' => array());
  }

  /**
   * Deserialize a node value
   * @param node A reference to the node
   * @param key The value name or type if value is an array
   * @param value The value or child data, if value is an array
   */
  protected function deserializeValue(Node $node, $key, $value) {
    if (!is_array($value)) {
      $node->setValue($key, $value);
    }
    else {
      $role = $key;
      if ($this->isMultiValued($node, $role)) {
        // deserialize children
        foreach($value as $childData) {
          $this->deserializeNode($childData, $node, $role);
        }
      }
      else {
        $this->deserializeNode($value, $node, $role);
      }
    }
  }

  /**
   * @see NodeSerializer::serializeNode
   */
  public function serializeNode(Node $node) {
    $this->_serializedOIDs = array();
    $serializedNode = $this->serializeNodeImpl($node);
    return $serializedNode;
  }

  /**
   * Actually serialize a Node into an array
   * @param node A reference to the node to serialize
   * @return The node serialized into an associated array or null, if
   *  the node parameter is not a Node instance (e.g. PersistentObjectProxy)
   */
  protected function serializeNodeImpl($node) {
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
            // add the reference to the relation attribute
            $curResult[$role][] = array('$ref' => $relatedNode->getOID()->__toString());
          }
        }
        else {
          // add the reference to the relation attribute
          $curResult[$role] = array('$ref' => $relatedNode->getOID()->__toString());
        }
      }
    }
    return $curResult;
  }

  /**
   * Check if a relation is multi valued
   * @param node The Node that has the relation
   * @param role The role of the relation
   */
  protected function isMultiValued(Node $node, $role) {
    $isMultiValued = false;
    $mapper = $node->getMapper();
    if ($mapper->hasRelation($role)) {
      $relation = $mapper->getRelation($role);
      $isMultiValued = $relation->isMultiValued();
    }
    return $isMultiValued;
  }
}
?>
