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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectComparator;
use wcmf\lib\persistence\PagingInfo;

/**
 * UnionQuery combines multiple query results to allow for sorting and paginating
 * over different queries.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UnionQuery {

  /**
   * Execute the provided queries
   * @param UnionQueryProvider $queryProvider
   * @param int $buildDepth
   * @param array<string> $orderby
   * @param PagingInfo $pagingInfo
   * @return array<PersistentObject>
   */
  public static function execute(UnionQueryProvider $queryProvider, int $buildDepth=BuildDepth::SINGLE, array $orderby=null, PagingInfo $pagingInfo=null): array {
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cacheSection = str_replace('\\', '.', __CLASS__);

    $queryIds = $queryProvider->getIds();
    $numQueries = sizeof($queryIds);

    // get cache key for stored offsets of previous page
    $pagingInfo = $pagingInfo ?: new PagingInfo(PagingInfo::SIZE_INFINITE);
    $page = $pagingInfo->getPage();
    $prevPage = $page > 1 ? $page-1 : 1;
    $prevPagingInfo = new PagingInfo($pagingInfo->getPageSize(), true);
    $prevPagingInfo->setPage($prevPage);
    $prevCacheKey = self::getCacheKey($queryIds, $buildDepth, $orderby, $prevPagingInfo);

    // get offsets
    $offsets = [];
    if ($page == 1) {
      $offsets = array_fill(0, $numQueries, 0);
    }
    else {
      if ($cache->exists($cacheSection, $prevCacheKey)) {
        $offsets = $cache->get($cacheSection, $prevCacheKey);
      }
      else {
        // previous offsets must be generated by loading the pages for pages other than first
        for ($i=1; $i<$page; $i++) {
          $tmpPagingInfo = new PagingInfo($pagingInfo->getPageSize(), true);
          $tmpPagingInfo->setPage($i);
          $tmpCacheKey = self::getCacheKey($queryIds, $buildDepth, $orderby, $tmpPagingInfo);
          if (!$cache->exists($cacheSection, $tmpCacheKey)) {
            self::execute($queryProvider, $buildDepth, $orderby, $tmpPagingInfo);
          }
          $offsets = $cache->get($cacheSection, $tmpCacheKey);
        }
      }
    }

    $tmpResult = [];
    $total = 0;
    for ($i=0, $countI=$numQueries; $i<$countI; $i++) {
      // collect n objects from each query
      $queryId = $queryIds[$i];

      // set paging info
      $tmpPagingInfo = new PagingInfo($pagingInfo->getPageSize(), false);
      $tmpPagingInfo->setOffset($offsets[$i]);

      $objects = $queryProvider->execute($queryId, $buildDepth, $orderby, $tmpPagingInfo);
      foreach ($objects as $object) {
        $object->setProperty('queryId', $queryId);
      }
      $tmpResult = array_merge($tmpResult, $objects);
      $total += $tmpPagingInfo->getTotalCount();
    }
    $pagingInfo->setTotalCount($total);

    // sort
    if ($orderby != null) {
      $comparator = new ObjectComparator($orderby);
      usort($tmpResult, [$comparator, 'compare']);
    }

    // truncate
    $result = array_slice($tmpResult, 0, $pagingInfo->getPageSize());

    // update offsets
    $counts = array_fill_keys($queryIds, 0);
    for ($i=0, $count=sizeof($result); $i<$count; $i++) {
      $counts[$result[$i]->getProperty('queryId')]++;
    }
    for ($i=0, $count=$numQueries; $i<$count; $i++) {
      $offsets[$i] += $counts[$queryIds[$i]];
    }
    $cacheKey = self::getCacheKey($queryIds, $buildDepth, $orderby, $pagingInfo);
    $cache->put($cacheSection, $cacheKey, $offsets);

    // remove objects for which the user is not authorized
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $result = array_filter($result, function($object) use ($permissionManager) {
      return $permissionManager->authorize($object->getOID(), '', PersistenceAction::READ);
    });

    return $result;
  }

  /**
   * Get a unique string for the given parameter values
   * @param mixed $ids
   * @param int $buildDepth
   * @param array<string> $orderby
   * @param PagingInfo $pagingInfo
   * @return string
   */
  private static function getCacheKey($ids, int $buildDepth, array $orderby=null, PagingInfo $pagingInfo=null): string {
    $result = is_array($ids) ? join(',', $ids) : $ids;
    $result .= ','.$buildDepth;
    if ($orderby != null) {
      $result .= ','.join(',', $orderby);
    }
    if ($pagingInfo != null) {
      $result .= ','.$pagingInfo->getOffset().','.$pagingInfo->getPageSize();
    }
    return hash('sha256', $result);
  }
}
?>
