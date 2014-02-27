<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\model;

use wcmf\lib\model\Node;

/**
 * NodeRelationComparator is used to compare nodes by their sortkey
 * in relation to a given node.
 *
 * The following example shows the usage:
 *
 * @code
 * // sort all child nodes of a node by their sortkey
 * // regardless of the type
 * $node = $persistenceFacade->load(ObjectId::parse($oidStr), 1);
 * $children = $node->getChildren();
 * $comparator = new NodeSortkeyComparator($node, $children);
 * usort($children, array($comparator, 'compare'));
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeSortkeyComparator {

  private $_referenceNode;
  private $_oidRoleMap = array();

  /**
   * Constructor
   * @param referenceNode Node instance to which the other nodes are related
   * @param nodeList Array of Node instances, which should be sorted in relation
   *   to the reference node
   * @note If the role of a node to sort is not be defined, the comparator
   * uses the first relation that has the type of the given node
   */
  public function __construct(Node $referenceNode, array $nodeList) {
    $this->_referenceNode = $referenceNode;
    // map oids to roles for faster access
    foreach ($nodeList as $curNode) {
      $curRole = $referenceNode->getNodeRelation($curNode);
      if ($curRole != null) {
        $this->_oidRoleMap[$curNode->getOID()->__toString()] = $curRole->getOtherRole();
      }
    }
  }

  /**
   * Compare function for sorting Nodes in the given relation
   * @param a First Node instance
   * @param b First Node instance
   * @return -1, 0 or 1 whether a is less, equal or greater than b
   *   in respect of the criteria
   */
  public function compare(Node $a, Node $b) {
    $valA = $this->getSortkeyValue($a);
    $valB = $this->getSortkeyValue($b);
    if ($valA == $valB) { return 0; }
    return ($valA > $valB) ? 1 : -1;
  }

  /**
   * Get the sortkey value of a node in the given relation
   * @param node Node
   * @return Number
   */
  protected function getSortkeyValue(Node $node) {
    // if no role is defined for a or b, the sortkey is
    // determined from the type of the reference node
    $defaultRole = $this->_referenceNode->getType();
    $referenceMapper = $this->_referenceNode->getMapper();
    $mapper = $node->getMapper();

    if ($referenceMapper && $mapper) {

      $referenceRole = $defaultRole;
      // get the sortkey of the node for the relation to the reference node,
      // if a role is defined
      $oidStr = $node->getOID()->__toString();
      if (isset($this->_oidRoleMap[$oidStr])) {
        $nodeRole = $this->_oidRoleMap[$oidStr];
        $relationDesc = $referenceMapper->getRelation($nodeRole);
        $referenceRole = $relationDesc->getThisRole();
      }
      $sortkeyDef = $mapper->getSortkey($referenceRole);
      return $node->getValue($sortkeyDef['sortFieldName']);
    }
    return 0;
  }
}
?>
