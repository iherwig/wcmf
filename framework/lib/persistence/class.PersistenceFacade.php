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
require_once(WCMF_BASE."wcmf/lib/util/class.ObjectFactory.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.Criteria.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.RelationDescription.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.AttributeDescription.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ReferenceDescription.php");

/**
 * Some constants describing the build process
 */
// TODO: make them constants in PersistenceFacade
define("BUILDDEPTH_INFINITE", -1);     // build complete tree from given root on
define("BUILDDEPTH_SINGLE",   -2);     // build only given object
define("BUILDDEPTH_REQUIRED", -4);     // build tree from given root on respecting the required property defined in element relations
define("BUILDDEPTH_PROXIES_ONLY", -8); // build only proxies
define("BUILDDEPTH_MAX", 10);          // maximum possible creation depth in one call

/**
 * @class PersistenceFacade
 * @ingroup Persistence
 * @brief PersistenceFacade instantiates the PersistenceFacade implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class PersistenceFacade
{
  private static $_instance = null;

  private function __construct() {}

  /**
   * Returns an instance of the PersistenceFacade implementation.
   * @return PersistenceFacade
   */
  public static function getInstance()
  {
    if (!isset(self::$_instance)) {
      self::$_instance = ObjectFactory::createInstanceFromConfig('implementation', 'PersistenceFacade');
    }
    return self::$_instance;
  }
  /**
   * Get a list of types defined in the application.
   * @return The list of types
   */
  abstract function getKnownTypes();
  /**
   * Check if a type is defined in the application.
   * @param type The type to check
   * @return True/False wether the type is defined or not
   */
  abstract function isKnownType($type);
  /**
   * Load an object from the database.
   * @param oid The object id of the object to construct
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param buildAttribs An assoziative array listing the attributes to load (default: null, loads all attributes)
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include (default: null, includes all types)
   * @return A reference to the object, null if oid does not exist or a given condition prevents loading.
   */
  abstract function load(ObjectId $oid, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null);
  /**
   * Construct the template of an Object of a given type.
   * @param type The type of object to build
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param buildAttribs An assoziative array listing the attributes to create (default: null, creates all attributes)
   *        (keys: the types, values: an array of attributes of the type to create)
   *        Use this to create only a subset of attributes
   * @return A reference to the object.
   */
  abstract function create($type, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null);
  /**
   * Save an object to the database (inserted if it is new).
   * @param object A reference to the object to save
   * @return True/False depending on success of operation
   */
  abstract function save(PersistentObject $object);
  /**
   * Delete an object from the database (together with all of its children).
   * @param oid The object id of the object to delete
   * @return True/False depending on success of operation
   */
  abstract function delete(ObjectId $oid);
  /**
   * Get the object id of the last created object of a given type.
   * @param type The type of the object
   * @return The object id or null
   */
  abstract function getLastCreatedOID($type);
  /**
   * Get the object ids of objects matching a given criteria. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @return An array containing the objects ids
   */
  abstract function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);
  /**
   * Get the first object id of objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @return An object id or null
   */
  abstract function getFirstOID($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);
  /**
   * Load the objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @param buildAttribs An assoziative array listing the attributes to load [default: null, loads all attributes]
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include [default: null, loads all types]
   * @return An array containing the objects
   */
  abstract function loadObjects($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
      $buildAttribs=null, $buildTypes=null);
  /**
   * Load the first object matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @param buildAttribs An assoziative array listing the attributes to load [default: null, loads all attributes]
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include [default: null, loads all types]
   * @return A reference to the object or null
   */
  abstract function loadFirstObject($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null);
  /**
   * Start a transaction. Used for PersistenceMapper classes that need to explicitely start and commit transactions.
   * If this method is called, the startTransaction() method of every used PersistenceMapper will be called - until
   * commitTransaction() is called.
   * @note There is only ONE transaction active at a time. Repeated calls of this method will leave the initial
   * transaction active until commitTransaction() ore rollbackTransaction() is called.
   */
  abstract function startTransaction();
  /**
   * Commit the started transaction. Used for PersistenceMapper classes that need to explicitely start and commit transactions.
   * If this method is called, the commitTransaction() method of every used PersistenceMapper will be called.
   * @note There is only ONE transaction active at a time. Repeated calls of this method will do nothing until
   * a new transaction was started by calling startTransaction().
   */
  abstract function commitTransaction();
  /**
   * Rollback the started transaction. Used for PersistenceMapper classes that need to explicitely start and commit transactions.
   * If this method is called, the rollbackTransaction() method of every used PersistenceMapper will be called.
   * @note There is only ONE transaction active at a time. Repeated calls of this method will do nothing until
   * a new transaction was started by calling startTransaction(). Rollbacks have to be supported by the data storage.
   */
  abstract function rollbackTransaction();
  /**
   * Get the PersistenceMapper for a given type. If no mapper for this type is defined the mapper for type '*' will be returned
   * @param type The type of the object to get the PersistenceMapper for
   * @return A reference to the PersistenceMapper, null on error
   */
  abstract function getMapper($type);
  /**
   * Explicitly set a PersistentMapper for a type
   * @param type The type to set the mapper for
   * @param mapper A reference to the mapper
   */
  abstract function setMapper($type, PersistenceMapper $mapper);
  /**
   * Get a mapper for a given configuration section
   * @param configSection The name of the configuration section (e.g. database)
   * @param mapper A reference to the mapper
   */
  abstract function getMapperForConfigSection($configSection);
  /**
   * Enable logging using a given OutputStrategy to log insert/update/delete actions to a file.
   * @param logStrategy The OutputStrategy to use.
   */
  abstract function enableLogging($logStrategy);
  /**
   * Disable logging.
   */
  abstract function disableLogging();
  /**
   * Check if the PersistenceMapper is logging.
   * @return True/False whether the PersistenceMapper is logging.
   */
  abstract function isLogging();
  /**
   * Set state to readonly. If set to true, PersistenceFacade will return only immutable
   * objects and save/delete methods are disabled.
   * @param isReadOnly True/False whether objects should be readonly or not
   */
  abstract function setReadOnly($isReadOnly);
  /**
   * Set state to caching. If set to true, PersistenceFacade will cache all loaded objects
   * and returns cached instances when calling the PersistenceFacade::load method.
   * @param isCaching True/False whether objects should be chached or not
   */
  abstract function setCaching($isCaching);
  /**
   * Clear the object cache
   */
  abstract function clearCache();
}
?>
