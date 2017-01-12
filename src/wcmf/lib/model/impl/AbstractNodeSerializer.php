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

use wcmf\lib\model\Node;
use wcmf\lib\model\NodeSerializer;

/**
 * NodeSerializerBase is a base class for NodeSerialize implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractNodeSerializer implements NodeSerializer {

  /**
   * Deserialize a node value
   * @param $node Node instance
   * @param $key The value name or type if value is an array
   * @param $value The value or child data, if value is an array
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
   * Check if a relation is multi valued
   * @param $node The Node that has the relation
   * @param $role The role of the relation
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
