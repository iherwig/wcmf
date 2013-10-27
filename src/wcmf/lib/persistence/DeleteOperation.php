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
   * @param entityType The type of PersistentObject to delete
   * @param criteria An array of criteria instances to select the objects on
   *          which the operation will be executed
   */
  public function __construct($entityType, array $criteria) {
    parent::__construct($entityType, array(), $criteria);
  }
}
?>
