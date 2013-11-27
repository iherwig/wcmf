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
namespace wcmf\lib\search;

use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\search\Search;

/**
 * IndexedSearch implementations are used to search entity objects
 * in a search index
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
interface IndexedSearch extends Search {

  /**
   * Reset the search index.
   */
  public function resetIndex();

  /**
   * Add/update a PersistentObject instance to/in the search index. This method modifies the
   * index. For that reason IndexedSearch::commitIndex() should be called afterwards.
   * @param obj The PersistentObject instance.
   */
  public function addToIndex(PersistentObject $obj);

  /**
   * Delete a PersistentObject instance from the search index. This method modifies the
   * index. For that reason IndexedSearch::commitIndex() should be called afterwards.
   * @param obj The PersistentObject instance.
   */
   public function deleteFromIndex(PersistentObject $obj);

  /**
   * Commit changes made on the index.
   * @note This method only commits the index if changes were made using the methods mentioned above.
   * @param optimize Boolean whether the index should be optimized after commit [default: true].
   */
  public function commitIndex($optimize = true);

  /**
   * Optimize the index
   */
  public function optimizeIndex();
}
?>
