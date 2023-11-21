<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence;

/**
 * UnionQueryProvider is used to provide queries to a union query.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface UnionQueryProvider {

  /**
   * Get identifiers for the contained queries
   * @return Array of string
   */
  public function getIds();

  /**
   * Execute a single query
   * NOTE Queries must load all objects regardless of set permissions. Authorization will be done in UnionQuery
   * @param $queryId
   * @param $buildDepth
   * @param $orderby
   * @param $pagingInfo
   * @return Array of PersistentObject instances
   */
  public function execute($queryId, $buildDepth, $orderby, $pagingInfo);
}
?>
