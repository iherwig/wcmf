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
 * @interface IPersistenceFacade
 * @ingroup Persistence
 * @brief IPersistenceFacade defines the interface for PersistenceFacade
 * implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface IPersistenceFacade
{
  /**
   * Get a list of types defined in the application.
   * @return The list of types
   */
  function getKnownTypes();
  /**
   * Check if a type is defined in the application.
   * @param type The type to check
   * @return True/False wether the type is defined or not
   */
  function isKnownType($type);
  /**
   * Load an object from the storage. The object will be attached to the transaction,
   * if the transaction is active.
   * @note The parameters buildDepth, buildAttribs and buildTypes are used to improve fetching
   * from the storage, but objects returned by this method are not guaranteed to only contain
   * the parameter values. This is especially true, if the same object was loaded before with
   * a wider fetch definition (e.g. greater buildDeph value)
   * @param oid The object id of the object to construct
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param buildAttribs An assoziative array listing the attributes to load (default: null, loads all attributes)
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include (default: null, includes all types)
   * @return PersistentObject, null if oid does not exist or a given condition prevents loading.
   */
  function load(ObjectId $oid, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null);
  /**
   * Construct the template of an object of a given type. The object will be
   * attached to the transaction, if the transaction is active.
   * @note If an object required to be transient, the IPersistentMapper::create() method or the class
   * constructor must be used.
   * @param type The type of object to build
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_INFINITE, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param buildAttribs An assoziative array listing the attributes to create (default: null, creates all attributes)
   *        (keys: the types, values: an array of attributes of the type to create)
   *        Use this to create only a subset of attributes
   * @return PersistentObject
   */
  function create($type, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null);
  /**
   * Get the object id of the last created object of a given type.
   * @param type The type of the object
   * @return ObjectId or null
   */
  function getLastCreatedOID($type);
  /**
   * Get the object ids of objects matching a given criteria. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @return Array containing the ObjectId instances
   */
  function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);
  /**
   * Get the first object id of objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance. [default: null]
   * @return ObjectId or null
   */
  function getFirstOID($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);
  /**
   * Load the objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param type The type of the object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the object's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @param buildAttribs An assoziative array listing the attributes to load [default: null, loads all attributes]
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include [default: null, loads all types]
   * @return Array containing the PersistentObject instances
   */
  function loadObjects($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
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
   * @return PersistentObject or null
   */
  function loadFirstObject($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null);
  /**
   * Get the current business transaction.
   * @note There is only one transaction instance at the same time.
   * @return ITransaction
   */
  function getTransaction();
  /**
   * Get the PersistenceMapper for a given type. If no mapper for this type is defined the mapper for type '*' will be returned
   * @param type The type of the object to get the PersistenceMapper for
   * @return IPersistenceMapper or null on error
   */
  function getMapper($type);
  /**
   * Explicitly set a PersistentMapper for a type
   * @param type The type to set the mapper for
   * @param mapper IPersistenceMapper instance
   */
  function setMapper($type, IPersistenceMapper $mapper);
  /**
   * Get a mapper for a given configuration section
   * @param configSection The name of the configuration section (e.g. database)
   * return IPersistenceMapper or null on error
   */
  function getMapperForConfigSection($configSection);
  /**
   * Enable logging using a given OutputStrategy to log insert/update/delete actions to a file.
   * @param logStrategy The OutputStrategy to use.
   */
  function enableLogging($logStrategy);
  /**
   * Disable logging.
   */
  function disableLogging();
  /**
   * Check if the PersistenceMapper is logging.
   * @return Boolean whether the PersistenceMapper is logging.
   */
  function isLogging();
  /**
   * Set state to readonly. If set to true, PersistenceFacade will return only immutable
   * objects and save/delete methods are disabled.
   * @param isReadOnly True/False whether objects should be readonly or not
   */
  function setReadOnly($isReadOnly);
}
?>
