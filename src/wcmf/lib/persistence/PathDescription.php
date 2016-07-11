<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

/**
 * PathDescription describes a path between two types.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PathDescription {

  protected $startType = '';
  protected $startRole = '';
  protected $endType = '';
  protected $endRole = '';

  protected $path = null;

  /**
   * Constructor.
   * @param $path Array of RelationDescription instances
   */
  public function __construct(array $path) {
    $this->path = $path;

    $pathSize = sizeof($path);
    if ($pathSize > 0) {
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
  public function getStartType() {
    return $this->startType;
  }

  /**
   * Get the role name at the start point
   * @return String
   */
  public function getStartRole() {
    return $this->startRole;
  }

  /**
   * Get the PersistentObject type at the end point
   * @return String
   */
  public function getEndType() {
    return $this->endType;
  }

  /**
   * Get the role name at the end point
   * @return String
   */
  public function getEndRole() {
    return $this->endRole;
  }

  /**
   * Get the path
   * @return Array of RelationDesctription instances
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Get the length of the path
   * @return int
   */
  public function getPathLength() {
    return sizeof($this->path);
  }
}
?>
