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
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeValueIterator.php");

/**
 * @class NodeSerializer
 * @ingroup Model
 * @brief NodeSerializer is used to serialize Nodes into an array representation
 * or deserialize an array representation into Nodes.
 * The format of serialized Nodes is defined in the Dionysos specification (See:
 * http://olympos.svn.sourceforge.net/viewvc/olympos/trunk/olympos/dionysos/docs/Dionysos%20Specification%20JSON.odt)
 *
 * The array representation is an associative array where the keys are:
 *
 * - className: The type of the Node (optional, since it may be deduced from the object id)
 * - oid: The object id of the Node
 * - isReference: True/False wether this Node is a reference or complete
 * - lastChange: A timestamp defining the point in time of the last change of the Node
 * - attributes: An assiciative array with the value names as keys and the appropriate values
 *               Relations to other Nodes are also contained in this array, where the relation
 *               name is the array key
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeSerializer
{
  private static $NODE_KEYS = array(
      'className',
      'oid',
      'isReference',
      'lastChange',
      'attributes'
  );
  private static $_serializedOIDs = array();

  /**
   * Check if the given data represent a serialized Node
   * @param data A variable of any type
   * @return boolean
   */
  public static function isSerializedNode($data)
  {
    if (is_object($data)) {
      $data = (array)$data;
    }
    $syntaxOk = (is_array($data) && isset($data['oid']) && isset($data['attributes']));
    // check for oid variables
    if ($syntaxOk && preg_match('/^\{.+\}$/', $data['oid'])) {
      $syntaxOk = false;
    }
    return $syntaxOk;
  }
  /**
   * Deserialize a Node from serialized data. Only values given in data are be set.
   * @param data An array containing the serialized Node data
   * @param parent The parent Node [default: null]
   * @param role The role of the serialized Node in relation to parent [default: null]
   * @return An array with keys 'node' and 'data' where the node
   * value is the Node instance and the data value is the
   * remaining part of data, that is not used for deserializing the Node
   */
  public static function deserializeNode($data, Node $parent=null, $role=null)
  {
    if (!isset($data['oid'])) {
      throw new IllegalArgumentException("Serialized Node data must contain an 'oid' parameter");
    }
    $oid = ObjectId::parse($data['oid']);
    if ($oid == null) {
      throw new IllegalArgumentException("The object id '".$oid."' is invalid");
    }
    else
    {
      // don't create all values by default (-> don't use PersistenceFacade::create() directly,
      // just for determining the class)
      $persistenceFacade = PersistenceFacade::getInstance();
      $class = get_class($persistenceFacade->create($oid->getType(), BUILDDEPTH_SINGLE));
      $node = new $class;
      $node->setOID($oid);

      if (isset($data['attributes'])) {
        foreach($data['attributes'] as $key => $value) {
          self::deserializeValue($node, $key, $value);
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
  }
  /**
   * Deserialize an node value
   * @param node A reference to the node
   * @param key The value name or type if value is an array
   * @param value The value or child data, if value is an array
   */
  protected static function deserializeValue(Node $node, $key, $value)
  {
    if (!is_array($value)) {
      $node->setValue($key, $value);
    }
    else
    {
      $role = $key;
      if (self::isMultiValued($node, $role)) {
        // deserialize children
        foreach($value as $childData) {
          self::deserializeNode($childData, $node, $role);
        }
      }
      else {
        self::deserializeNode($value, $node, $role);
      }
    }
  }
  /**
   * Serialize a Node into an array
   * @param node A reference to the node to serialize
   * @return The node serialized into an associated array
   */
  public static function serializeNode(Node $node)
  {
    self::$_serializedOIDs = array();
    $serializedNode = self::serializeNodeImpl($node);
    return $serializedNode;
  }

  /**
   * Actually serialize a Node into an array
   * @param node A reference to the node to serialize
   * @return The node serialized into an associated array
   */
  protected static function serializeNodeImpl(Node $node)
  {
    $curResult = array();
    $curResult['className'] = $node->getType();
    $curResult['oid'] = $node->getOID()->__toString();
    $curResult['lastChange'] = strtotime($node->getValue('modified'));

    $oidStr = $node->getOID()->__toString();
    if (in_array($oidStr, self::$_serializedOIDs))
    {
      // the node is serialized already
      $curResult['isReference'] = true;
    }
    else
    {
      // the node is not serialized yet
      $curResult['isReference'] = false;
      self::$_serializedOIDs[] = $oidStr;

      // serialize attributes
      // use NodeValueIterator to iterate over all Node values
      $values = array();
      $valueIter = new NodeValueIterator($node, false);
      while (!$valueIter->isEnd())
      {
        $curIterNode = $valueIter->getCurrentNode();
        $valueName = $valueIter->getCurrentAttribute();
        $values[$valueName] = $curIterNode->getValue($valueName);
        $valueIter->proceed();
      }
      $curResult['attributes'] = $values;

      // add related objects by creating an attribute that is named as the role of the object
      // multivalued relations will be serialized into an array
      $mapper = $node->getMapper();
      foreach ($mapper->getRelations() as $relation)
      {
        $role = $relation->getOtherRole();
        $relatedNodes = $node->getValue($role);
        if ($relatedNodes)
        {
          // serialize the nodes
          $isMultiValued = $relation->isMultiValued();
          if ($isMultiValued)
          {
            $curResult['attributes'][$role] = array();
            foreach ($relatedNodes as $relatedNode)
            {
              $data = self::serializeNodeImpl($relatedNode);
              // add the data to the relation attribute
              $curResult['attributes'][$role][] = $data;
            }
          }
          else {
              $data = self::serializeNodeImpl($relatedNodes);
              // add the data to the relation attribute
              $curResult['attributes'][$role] = $data;
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
  protected static function isMultiValued(Node $node, $role)
  {
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
