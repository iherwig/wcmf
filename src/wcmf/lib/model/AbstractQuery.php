<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBMapper;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceException;

/**
 * AbstractQuery is the base class for all query classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractQuery {

  private $selectStmt = null;

  /**
   * Get the logger
   * @return Logger
   */
  protected abstract function getLogger();

  /**
   * Get the name of the type that should be queried
   * @return String
   */
  protected abstract function getQueryType();

  /**
   * Execute the query
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   * or false if only object ids should be returned
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null) (default: _null_)
   * @param $pagingInfo A reference paging info instance (optional, default: _null_)
   * @return A list of objects that match the given conditions or a list of object ids
   */
  public function execute($buildDepth, $orderby=null, $pagingInfo=null) {
    // build the query
    $this->selectStmt = $this->buildQuery($buildDepth, $orderby, $pagingInfo);

    $result = $this->executeInternal($this->selectStmt, $buildDepth, $pagingInfo);
    $logger = $this->getLogger();
    if ($logger && $logger->isDebugEnabled()) {
      $logger->debug("Executed query: ".$this->selectStmt->__toString());
      $logger->debug("With parameters: ".json_encode($this->selectStmt->getParameters()));
      $logger->debug("Result: ".join(", ", array_map(function($item) {
        return preg_replace( "/\r|\n/", " ", $item->__toString());
      }, $result)));
    }
    return $result;
  }

  /**
   * Execute multiple queries
   * @param $queries
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   * or false if only object ids should be returned
   * @param $orderby
   * @param $pagingInfo
   * @return A list of objects that match the given conditions or a list of object ids
   */
  public static function executeMultiple(array $queries, $buildDepth, $orderby=null, $pagingInfo=null) {
    $numQueries = sizeof($queries);
    $cache = ObjectFactory::getInstance('dynamicCache');
    $cacheSection = str_replace('\\', '.', __CLASS__);

    // get types
    $types = [];
    for ($i=0, $count=$numQueries; $i<$count; $i++) {
      $types[] = $queries[$i]->getQueryType();
    }

    // get cache key for stored offsets of previous page
    $pagingInfo = $pagingInfo ?: new PagingInfo(PagingInfo::SIZE_INFINITE);
    $page = $pagingInfo->getPage();
    $prevPage = $page > 1 ? $page-1 : 1;
    $prevPagingInfo = new PagingInfo($pagingInfo->getPageSize(), true);
    $prevPagingInfo->setPage($prevPage);
    $prevCacheKey = $this->getCacheKey($types, $buildDepth, $orderby, $prevPagingInfo);

    // get offsets
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
          $tmpCacheKey = $this->getCacheKey($types, $buildDepth, $orderby, $tmpPagingInfo);
          if (!$cache->exists($cacheSection, $tmpCacheKey)) {
            $this->executeMultiple($queries, $buildDepth, $orderby, $tmpPagingInfo);
          }
          $offsets = $cache->get($cacheSection, $tmpCacheKey);
        }
      }
    }

    $tmpResult = [];
    $total = 0;
    for ($i=0, $countI=$numQueries; $i<$countI; $i++) {
      // collect n objects from each type

      // set paging info
      $tmpPagingInfo = new PagingInfo($pagingInfo->getPageSize(), false);
      $tmpPagingInfo->setOffset($offsets[$i]);

      $objects = $queries[$i]->execute($buildDepth, $orderby, $tmpPagingInfo);
      $tmpResult = array_merge($tmpResult, $objects);
      $total += $tmpPagingInfo->getTotalCount();
    }
    $pagingInfo->setTotalCount($total);

    // sort
    if ($orderby != null) {
      $comparator = new NodeComparator($orderby);
      usort($tmpResult, [$comparator, 'compare']);
    }

    // truncate
    $result = array_slice($tmpResult, 0, $pagingInfo->getPageSize());

    // update offsets
    $counts = array_fill_keys($types, 0);
    for ($i=0, $count=sizeof($result); $i<$count; $i++) {
      $counts[$result[$i]->getType()]++;
    }
    for ($i=0, $count=$numQueries; $i<$count; $i++) {
      $offsets[$i] += $counts[$types[$i]];
    }
    $cacheKey = $this->getCacheKey($types, $buildDepth, $orderby, $pagingInfo);
    $cache->put($cacheSection, $cacheKey, $offsets);

    return $result;
  }

  /**
   * Get the query serialized to a string. Placeholder are replaced with quoted values.
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   * or false if only object ids should be returned (optional, default: _BuildDepth::SINGLE_)
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @return String
   */
  public function getQueryString($buildDepth=BuildDepth::SINGLE, $orderby=null) {
    $selectStmt = $this->buildQuery($buildDepth, $orderby);
    $str = $selectStmt->__toString();
    $mapper = self::getMapper($selectStmt->getType());
    foreach ($selectStmt->getParameters() as $key => $value) {
      $value = is_string($value) ? $mapper->quoteValue($value) : $value;
      $str = preg_replace('/'.$key.'/', $value, $str, 1);
    }
    return $str;
  }

  /**
   * Get the query last executed serialized to a string.
   * @return String
   */
  public function getLastQueryString() {
    if ($this->selectStmt != null) {
      return $this->selectStmt->__toString();
    }
    return "";
  }

  /**
   * Build the query
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   * or false if only object ids should be returned
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param $pagingInfo A reference paging info instance (optional, default: _null_)
   * @return SelectStatement instance
   */
  protected abstract function buildQuery($buildDepth, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * Execute the query and return the results.
   * @param $selectStmt A SelectStatement instance
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   *        or false if only object ids should be returned
   * @param $pagingInfo A reference paging info instance (default: _null_)
   * @return A list of objects that match the given conditions or a list of object ids
   */
  protected function executeInternal(SelectStatement $selectStmt, $buildDepth, PagingInfo $pagingInfo=null) {
    $type = $this->getQueryType();
    $mapper = self::getMapper($type);
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $loadOidsOnly = ($buildDepth === false);

    // execute the query
    $result = [];
    if ($loadOidsOnly) {
      $data = $mapper->select($selectStmt, $pagingInfo);

      // collect oids
      for ($i=0, $count=sizeof($data); $i<$count; $i++) {
        $oid = $mapper->constructOID($data[$i]);
        if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
          $result[] = $oid;
        }
      }
    }
    else {
      $objects = $mapper->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo);

      // remove objects for which the user is not authorized
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $tx = $persistenceFacade->getTransaction();

      // remove objects for which the user is not authorized
      for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
        $object = $objects[$i];
        $oid = $object->getOID();
        if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
          // call lifecycle callback
          $object->afterLoad();
          $result[] = $object;
        }
        else {
          $tx->detach($oid);
        }
        // TODO remove attribute values for which the user is not authorized
        // should use some pre-check if restrictions on the entity type exist
      }
    }
    return $result;
  }

  /**
   * Get the database connection of the given node type.
   * @param $type The node type to get the connection from connection
   * @return The connection
   */
  protected static function getConnection($type) {
    $mapper = self::getMapper($type);
    $conn = $mapper->getConnection();
    return $conn;
  }

  /**
   * Get the mapper for a Node and check if it is a supported one.
   * @param $type The type of Node to get the mapper for
   * @return RDBMapper instance
   */
  protected static function getMapper($type) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($type);
    if (!($mapper instanceof RDBMapper)) {
      throw new PersistenceException('Only PersistenceMappers of type RDBMapper are supported.');
    }
    return $mapper;
  }

  /**
   * Get a unique string for the given parameter values
   * @param $typeOrTypes
   * @param $buildDepth
   * @param $orderArray
   * @param $pagingInfo
   * @return String
   */
  protected function getCacheKey($typeOrTypes, $buildDepth, $orderArray=null, PagingInfo $pagingInfo=null) {
    $result = is_array($typeOrTypes) ? join(',', $typeOrTypes) : $typeOrTypes;
    $result .= ','.$buildDepth;
    if ($orderArray != null) {
      $result .= ','.join(',', $orderArray);
    }
    if ($pagingInfo != null) {
      $result .= ','.$pagingInfo->getOffset().','.$pagingInfo->getPageSize();
    }
    return $result;
  }
}
?>
