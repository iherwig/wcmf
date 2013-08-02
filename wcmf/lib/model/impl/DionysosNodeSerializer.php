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
 * DionysosNodeSerializer is used to serialize Nodes into the Dionysos format and
 * vice versa. The format of serialized Nodes is defined in the Dionysos
 * specification (See: http://olympos.svn.sourceforge.net/viewvc/olympos/trunk/olympos/dionysos/docs/Dionysos%20Specification%20JSON.odt)
 *
 * The array representation is an associative array where the keys are:
 *
 * - className: The type of the Node (optional, if oid is given)
 * - oid: The object id of the Node (optional, if className is given)
 * - isReference: Boolean wether this Node is a reference or complete
 * - lastChange: A timestamp defining the point in time of the last change of the Node
 * - attributes: An associative array with the value names as keys and the appropriate values
 *               Relations to other Nodes are also contained in this array, where the relation
 *               name is the array key
 * @author ingo herwig <ingo@wemove.com>
 */
class DionysosNodeSerializer implements NodeSerializer {

  private static $NODE_KEYS = array(
      'className',
      'oid',
      'isReference',
      'lastChange',
      'attributes'
  );
  private $_serializedOIDs = array();

  /**
   * @see NodeSerializer::isSerializedNode
   */
  public function isSerializedNode($data) {
    if (is_object($data)) {
      $data = (array)$data;
    }
    $syntaxOk = (is_array($data) &&
            (isset($data['oid']) || isset($data['className'])) &&
            isset($data['attributes']));
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
    if (!isset($data['className']) && !isset($data['oid'])) {
      throw new IllegalArgumentException("Serialized Node data must contain an 'className' or 'oid' parameter");
    }
    // create a dummy oid, if not given
    $oid = null;
    if (!isset($data['oid'])) {
      $oid = new ObjectId($data['className']);
    }
    else {
      $oid = ObjectId::parse($data['oid']);
      if ($oid == null) {
        throw new IllegalArgumentException("The object id '".$oid."' is invalid");
      }
    }

    // don't create all values by default (-> don't use PersistenceFacade::create() directly,
    // just for determining the class)
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $class = get_class($persistenceFacade->create($oid->getType(), BuildDepth::SINGLE));
    $node = new $class;
    $node->setOID($oid);

    if (isset($data['attributes'])) {
      foreach($data['attributes'] as $key => $value) {
        $this->deserializeValue($node, $key, $value);
      }
    }
    if ($parent != null) {
      $parent->addNode($node, $role);
    }

    // get remaining part of data
    foreach ($data as $key => $value) {
      if (in_array($key, self::$NODE_KEYS)) {
        unset($data[$key]);
      }
    }
    return array('node' => $node, 'data' => $data);
  }

  /**
   * Deserialize a node value
   * @param node A reference to the node
   * @param key The value name or type if value is an array
   * @param value The value or child data, if value is an array
   */
  protected function deserializeValue(Node $node, $key, $value) {
    if (!is_array($value)) {
      // force set value to avoid exceptions in this stage
      $node->setValue($key, $value, true);
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
    $curResult['className'] = $node->getType();
    $curResult['oid'] = $node->getOID()->__toString();
    $curResult['lastChange'] = strtotime($node->getValue('modified'));

    $oidStr = $node->getOID()->__toString();
    if (in_array($oidStr, $this->_serializedOIDs)) {
      // the node is serialized already
      $curResult['isReference'] = true;
    }
    else {
      // the node is not serialized yet
      $curResult['isReference'] = false;
      $this->_serializedOIDs[] = $oidStr;

      // serialize attributes
      // use NodeValueIterator to iterate over all Node values
      $values = array();
      $valueIter = new NodeValueIterator($node, false);
      foreach($valueIter as $valueName => $value) {
        $values[$valueName] = $value;
      }
      $curResult['attributes'] = $values;

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
            $curResult['attributes'][$role] = array();
            foreach ($relatedNodes as $relatedNode) {
              $data = $this->serializeNodeImpl($relatedNode);
              if ($data != null) {
                // add the data to the relation attribute
                $curResult['attributes'][$role][] = $data;
              }
            }
          }
          else {
            $data = $this->serializeNodeImpl($relatedNodes);
            if ($data != null) {
              // add the data to the relation attribute
              $curResult['attributes'][$role] = $data;
            }
          }
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
