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
namespace wcmf\lib\persistence;

use wcmf\lib\persistence\PersistenceOperation;

/**
 * DeleteOperation holds data necessary to accomplish
 * an delete operation on the persistent store.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DeleteOperation extends PersistenceOperation {

  /**
   * Constructor.
   * @param $entityType The type of PersistentObject to delete
   * @param $criteria An array of criteria instances to select the objects on
   *          which the operation will be executed
   */
  public function __construct($entityType, array $criteria) {
    parent::__construct($entityType, array(), $criteria);
  }
}
?>
