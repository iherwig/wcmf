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
 * @class PathDescription
 * @ingroup Persistence
 * @brief Instances of PathDescription describe a path between two types.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PathDescription
{
  protected $startType = '';
  protected $startRole = '';
  protected $endType = '';
  protected $endRole = '';

  protected $path = null;

  /**
   * Constructor.
   * @param path Array of RelationDescription instances
   */
  public function __construct(array $path)
  {
    $this->path = $path;

    $pathSize = sizeof($path);
    if ($pathSize > 0)
    {
      $firstPathPart = $path[0];
      $lastPathPart = $path[$pathSize-1];
      $this->startType = $firstPathPart->getThisType();
      $this->startRole = $firstPathPart->getThisRole();
      $this->endType = $lastPathPart->getOtherType();
      $this->endRole = $lastPathPart->getOtherRole();
    }
  }

  /**
   * Get the PersistentObject type at the start point
   * @return String
   */
  public function getStartType()
  {
    return $this->startType;
  }

  /**
   * Get the role name at the start point
   * @return String
   */
  public function getStartRole()
  {
    return $this->startRole;
  }

  /**
   * Get the PersistentObject type at the end point
   * @return String
   */
  public function getEndType()
  {
    return $this->endType;
  }

  /**
   * Get the role name at the end point
   * @return String
   */
  public function getEndRole()
  {
    return $this->endRole;
  }

  /**
   * Get the path
   * @return Array of RelationDesctription instances
   */
  public function getPath()
  {
    return $this->path;
  }

  /**
   * Get the length of the path
   * @return int
   */
  public function getPathLength()
  {
    return sizeof($this->path);
  }
}
?>
