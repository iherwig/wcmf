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
namespace wcmf\lib\persistence;

use wcmf\lib\persistence\PersistenceOperation;

/**
 * InsertOperation holds data necessary to accomplish
 * an insert operation on the persistent store.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InsertOperation extends PersistenceOperation {

  /**
   * Constructor.
   * @param entityType The type of PersistentObject to insert
   * @param values An array of attribute/value pairs to apply
   */
  public function __construct($entityType, array $values) {
    parent::__construct($entityType, $values, array());
  }
}
?>
