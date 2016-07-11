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
namespace wcmf\lib\search;

use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistentObject;

/**
 * Search implementations are used to search entity objects.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Search {

  /**
   * Check if the search word is valid
   * @param $word
   * @return True, if valid, error message else
   */
  public function check($word);

  /**
   * Search for searchTerm
   * @param $searchTerm
   * @param $pagingInfo A PagingInfo instance (optional, default: _null_)
   * @return Associative array with object ids as keys and
   * associative array with keys 'oid', 'score', 'summary' as value
   */
  public function find($searchTerm, PagingInfo $pagingInfo=null);

  /**
   * Check if the instance object is searchable
   * (defined by the property 'is_searchable')
   * @param $obj PersistentObject instance
   * @return Boolean whether the object is searchable or not
   */
  public function isSearchable(PersistentObject $obj);
}
?>
