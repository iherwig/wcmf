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

/**
 * @class Position
 * @ingroup Util
 * @brief The Position class stores a coordinate tuple for use
 * with the LayoutVisitor.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Position
{
  var $x;
  var $y;
  var $z;
  
  /**
   * Constructor.
   * @param x, y, z The coordinates.
   */
  function Position($x, $y, $z)
  {
    $this->x = $x;
    $this->y = $y;
    $this->z = $z;
  }
}
?>