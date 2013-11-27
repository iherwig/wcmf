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

/**
 * Search implementations are used to search entity objects.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
interface Search {

  /**
   * Search for searchTerm
   * @param searchTerm
   * @return Associative array with object ids as keys and
   * associative array with keys 'oid', 'score', 'summary' as value
   */
  public function find($searchTerm);

  /**
   * Check if the instance object is searchable
   * (defined by the property 'is_searchable')
   * @param obj PersistentObject instance
   * @return Boolean wether the object is searchable or not
   */
  public function isSearchable(PersistentObject $obj);
}
?>
