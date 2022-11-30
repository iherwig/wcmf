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

/**
 * DionysosNodeSerializer is used to serialize Nodes into the Dionysos format and
 * vice versa. The format of serialized Nodes is defined in the Dionysos
 * specification (See: http://olympos.svn.sourceforge.net/viewvc/olympos/trunk/olympos/dionysos/docs/Dionysos%20Specification%20JSON.odt)
 *
 * The array representation is an associative array where the keys are:
 *
 * - className: The type of the Node (optional, if oid is given)
 * - oid: The object id of the Node (optional, if className is given)
 * - isReference: Boolean whether this Node is a reference or complete
 * - lastChange: A timestamp defining the point in time of the last change of the Node
 * - attributes: An associative array with the value names as keys and the appropriate values
 *               Relations to other Nodes are also contained in this array, where the relation
 *               name is the array key
 * @author ingo herwig <ingo@wemove.com>
 */
class DionysosNodeSerializer extends AbstractNodeSerializer {

  const NODE_KEYS = [
      'className',
      'oid',
      'isReference',
      'lastChange',
      'attributes'
  ];
  private $serializedOIDs = [];

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

    // create request node
    $node = $this->getNodeTemplate($oid);
    if (isset($data['attributes'])) {
      foreach($data['attributes'] as $key => $value) {
        $this->deserializeValue($node, $key, $value);
      }
    }

    // set oid after attributes in order to
    // avoid it being changed from missing pk values
    $node->setOID($oid);

    // create hierarchy
    if ($parent != null) {
      $parent->addNode($node, $role);
    }

    // get remaining part of data
    foreach ($data as $key => $value) {
      if (in_array($key, self::NODE_KEYS)) {
        unset($data[$key]);
      }
    }
    return ['node' => $node, 'data' => $data];
  }

  /**
   * @see NodeSerializer::serializeNode
   */
  public function serializeNode($node) {
    if (!($node instanceof Node)) {
      return null;
    }
    $curResult = [];
    $curResult['className'] = $node->getType();
    $curResult['oid'] = $node->getOID()->__toString();
    $curResult['lastChange'] = strtotime($node->getValue('modified') ?? '');

    $oidStr = $node->getOID()->__toString();
    if (in_array($oidStr, $this->serializedOIDs)) {
      // the node is serialized already
      $curResult['isReference'] = true;
    }
    else {
      // the node is not serialized yet
      $curResult['isReference'] = false;
      $this->serializedOIDs[] = $oidStr;

      // serialize attributes
      // use NodeValueIterator to iterate over all Node values
      $values = [];
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
            $curResult['attributes'][$role] = [];
            foreach ($relatedNodes as $relatedNode) {
              $data = $this->serializeNode($relatedNode);
              if ($data != null) {
                // add the data to the relation attribute
                $curResult['attributes'][$role][] = $data;
              }
            }
          }
          else {
            $data = $this->serializeNode($relatedNodes);
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
}
?>
