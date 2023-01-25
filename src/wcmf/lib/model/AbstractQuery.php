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
   * Get the name of the entity type that should be queried
   * @return String
   */
  public abstract function getQueryType();

  /**
   * Execute the query
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   * or false if only object ids should be returned
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null) (default: _null_)
   * @param $pagingInfo A reference paging info instance (optional, default: _null_)
   * @return array of objects that match the given conditions or a list of object ids
   */
  public function execute($buildDepth, $orderby=null, $pagingInfo=null): array {
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
}
?>
