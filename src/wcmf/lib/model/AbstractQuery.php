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
namespace wcmf\lib\model;

use \Zend_Db_Select;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\mapper\RDBMapper;
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
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   * or false if only object ids should be returned
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference paging info instance, optional [default null].
   * @param attribs An array of attributes to load (null to load all). [default: null]
   * @return A list of objects that match the given conditions or a list of object ids
   */
  public function execute($buildDepth, $orderby=null, $pagingInfo=null, $attribs=null) {
    // build the query
    $this->_selectStmt = $this->buildQuery($orderby, $attribs);

    return $this->executeInternal($this->_selectStmt, $buildDepth, $pagingInfo, $attribs);
  }

  /**
   * Get the query serialized to a string.
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param attribs An array of attributes to load (null to load all). [default: null]
   * @return String
   */
  public function getQueryString($orderby=null, $attribs=null) {
    $selectStmt = $this->buildQuery($orderby, $attribs);
    return $selectStmt->__toString();
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
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param attribs An array of attributes to load (null to load all). [default: null]
   * @return Zend_Db_Select instance
   */
  protected abstract function buildQuery($orderby=null, $attribs=null);

  /**
   * Execute the query and return the results.
   * @param selectStmt A Zend_Db_Select instance
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to load (except BuildDepth::REQUIRED)
   *        or false if only object ids should be returned
   * @param pagingInfo A reference paging info instance. [default: null]
   * @param attribs An array of attributes to load (null to load all). [default: null]
   * @return A list of objects that match the given conditions or a list of object ids
   */
  protected function executeInternal(Zend_Db_Select $selectStmt, $buildDepth, PagingInfo
          $pagingInfo=null, $attribs=null) {
    $type = $this->getQueryType();
    $loadOidsOnly = ($buildDepth === false);

    // load only the necessary attributes if only object ids are requested
    if ($loadOidsOnly) {
      $attribs = array();
      $buildDepth = BuildDepth::SINGLE;
    }

    // convert attribs to buildAttribs format used by the mapper
    $buildAttribs = array($type => $attribs);

    // execute the query
    $mapper = self::getMapper($type);
    $objects = $mapper->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo, $buildAttribs);

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
   * @param type The node type to get the connection from connection
   * @return The connection
   */
  protected static function getConnection($type) {
    $mapper = self::getMapper($type);
    $conn = $mapper->getConnection();
    return $conn;
  }

  /**
   * Get the mapper for a Node and check if it is a supported one.
   * @param type The type of Node to get the mapper for
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
   * @param mapper PersistenceMapper instance
   * Throws an Exception
   */
  protected static function checkMapper(PersistenceMapper $mapper) {
    if (!($mapper instanceof RDBMapper)) {
      throw new PersistenceException(Message::get('Only PersistenceMappers of type RDBMapper are supported.'));
    }
  }
}
?>
