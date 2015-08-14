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
namespace wcmf\lib\model\visitor;

use wcmf\lib\model\NodeIterator;
use wcmf\lib\model\output\Position;
use wcmf\lib\model\visitor\Visitor;

/**
 * LayoutVisitor is used to position a tree of objects on a plane (the objects must implement the getParent()).
 * It uses a simple algorithm that positions the objects on a discrete array with distance 1 so
 * that all leaves are equal distant from their neighbours.
 * The positions are stored in a map that is provided by the getMap() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LayoutVisitor extends Visitor
{
  const MAPTYPE_HORIZONTAL = 0;
  const MAPTYPE_VERTICAL = 1;

  private $_map = array();

  /**
   * Constructor.
   */
  public function __construct() {
    // set default maptype
    $this->_map["type"] = self::MAPTYPE_HORIZONTAL;
  }

  /**
   * Visit the current object in iteration and position it on the map.
   * @param $obj A reference to the current object.
   */
  public function visit($obj) {
    $this->_map[$obj->getOID()] = $this->calculatePosition($obj);
  }

  /**
   * Visit the current object in iteration and position it on the map.
   * @return The object map as assioziative array (key: object Id, value: Position).
   * map["type"] = MAPTYPE_HORIZONTAL | MAPTYPE_VERTICAL
   */
  public function getMap() {
    return $this->_map;
  }

  /**
   * Flip layout (x <-> y).
   */
  public function flip() {
    foreach($this->_map as $key => $position) {
      $temp = $position->x;
      $position->x = $position->y;
      $position->y = $temp;
      $this->_map[$key] = $position;
    }
    // switch map type
    $this->_map["type"] = 1-$this->_map["type"];
  }

  /**
   * Calculate the object's position using the described algorithm.
   * @param $obj A reference to the current object.
   */
  private function calculatePosition($obj) {
    $parent = & $obj->getParent();
    if (!$parent) {
      // start here
      $position = new Position(0,0,0);
    }
    else {
      $position = new Position(0,0,0);
      $parentPos = $this->_map[$parent->getOID()];
      $position->y = $parentPos->y + 1;
      $position->z = $parentPos->z + 1;

      $siblings = $parent->getChildren();
      if ($siblings[0]->getOID() == $obj->getOID()) {
        $position->x = $parentPos->x;
      }
      else {
        // find leftmost sibling of object
        for ($i=0;$i<sizeOf($siblings);$i++) {
          if ($siblings[$i]->getOID() == $obj->getOID()) {
            $leftSibling = & $siblings[$i-1];
          }
        }
        // find x-coordinate of rightmost descendant of leftmost sibling
        $maxX = 0;
        $nIter = new NodeIterator($leftSibling);
        foreach($nIter as $oid => $curObject) {
          $curPosition = $this->_map[$curObject->getOID()];
          if ($curPosition->x >= $maxX) {
            $maxX = $curPosition->x;
          }
         }
        $position->x = $maxX+2;
        // reposition parents
        while ($parent != null) {
          $this->_map[$parent->getOID()]->x += 1;
          $parent = $parent->getParent();
        }
      }
    }
    return $position;
  }
}
?>
