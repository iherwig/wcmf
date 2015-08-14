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
namespace wcmf\lib\model\output;

/**
 * The Position class stores a coordinate tuple for use
 * with the LayoutVisitor.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Position {
  public $x;
  public $y;
  public $z;

  /**
   * Constructor.
   * @param $x
   * @param $y
   * @param $z
   */
  public function __construct($x, $y, $z) {
    $this->x = $x;
    $this->y = $y;
    $this->z = $z;
  }
}
?>