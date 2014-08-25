<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\mapper\RDBMapper;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceException;

/**
 * AbstractQuery is the base class for all query classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractQuery {

  private $_selectStmt = null;

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
    $this->_selectStmt = $this->buildQuery($orderby, $pagingInfo);

    return $this->executeInternal($this->_selectStmt, $buildDepth, $pagingInfo);
  }

  /**
   * Get the query serialized to a string. Placeholder are replaced with quoted values.
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @return String
   */
  public function getQueryString($orderby=null) {
    $selectStmt = $this->buildQuery($orderby);
    $str = $selectStmt->__toString();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($selectStmt->getType());
    foreach ($selectStmt->getBind() as $value) {
      $str = preg_replace('/\?/', $mapper->quoteValue($value), $str, 1);
    }
    return $str;
  }

  /**
   * Get the query last executed serialized to a string.
   * @return String
   */
  public function getLastQueryString() {
    if ($this->_selectStmt != null) {
      return $this->_selectStmt->__toString();
    }
    return "";
  }

  /**
   * Build the query
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param $pagingInfo A reference paging info instance (optional, default: _null_)
   * @return SelectStatement instance
   */
  protected abstract function buildQuery($orderby=null, PagingInfo $pagingInfo=null);

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
    $loadOidsOnly = ($buildDepth === false);

    // load only the necessary attributes if only object ids are requested
    if ($loadOidsOnly) {
      $buildDepth = BuildDepth::SINGLE;
    }

    // execute the query
    $mapper = self::getMapper($type);
    $objects = $mapper->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo);

    // remove objects for which the user is not authorized
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    $result = array();
    for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
      $object = $objects[$i];
      if ($permissionManager->authorize($object->getOID(), '', PersistenceAction::READ)) {
        $result[] = $object;
      }
    }

    // transform the result
    if ($loadOidsOnly) {
      // collect oids
      $oids = array();
      foreach ($result as $object) {
        $oids[] = $object->getOID();
      }
      $result = $oids;
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
    self::checkMapper($mapper);
    return $mapper;
  }

  /**
   * Check if a mapper is a supported one.
   * @param $mapper PersistenceMapper instance
   * @throws PersistenceException
   */
  protected static function checkMapper(PersistenceMapper $mapper) {
    if (!($mapper instanceof RDBMapper)) {
      throw new PersistenceException(Message::get('Only PersistenceMappers of type RDBMapper are supported.'));
    }
  }
}
?>
