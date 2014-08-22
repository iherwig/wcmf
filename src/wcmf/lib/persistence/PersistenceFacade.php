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
namespace wcmf\lib\persistence;

use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;

/**
 * PersistenceFacade defines the interface for PersistenceFacade
 * implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PersistenceFacade {

  /**
   * Get a list of types defined in the application.
   * @return The list of fully qualified type names
   */
  function getKnownTypes();

  /**
   * Check if a type is defined in the application.
   * @param $type The type to check (either fully qualified or simple, if not ambiguous)
   * @return Boolean whether the type is defined or not
   */
  function isKnownType($type);

  /**
   * Get the fully qualified type name for a given simple type name.
   * @param $type Type name without namespace
   * @return Fully qualified type name (with namespace)
   */
  function getFullyQualifiedType($type);

  /**
   * Get the simple type name for a given fully qualified type name.
   * @param $type Type name with namespace
   * @return Simple type name (without namespace)
   */
  function getSimpleType($type);

  /**
   * Load an object from the storage. The object will be attached to the transaction,
   * if the transaction is active.
   * @note The parameter buildDepth is used to improve fetching from the storage, but objects
   * returned by this method are not guaranteed to only contain the parameter values.
   * This is especially true, if the same object was loaded before with a wider fetch definition
   * (e.g. greater buildDeph value)
   * @param $oid The object id of the object to construct
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) [default: BuildDepth::SINGLE]
   * @return PersistentObject, null if oid does not exist or a given condition prevents loading.
   */
  function load(ObjectId $oid, $buildDepth=BuildDepth::SINGLE);

  /**
   * Construct the template of an object of a given type. The object will be
   * attached to the transaction, if the transaction is active.
   * @note If an object required to be transient, the IPersistentMapper::create() method or the class
   * constructor must be used.
   * @param $type The type of object to build (either fully qualified or simple, if not ambiguous)
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::INFINITE, BuildDepth::PROXIES_ONLY) [default: BuildDepth::SINGLE]
   * @return PersistentObject
   */
  function create($type, $buildDepth=BuildDepth::SINGLE);

  /**
   * Get the object id of the last created object of a given type.
   * @param $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @return ObjectId or null
   */
  function getLastCreatedOID($type);

  /**
   * Get the object ids of objects matching a given criteria. If a PagingInfo instance is passed it will be used and updated.
   * @param $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @param $criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param $pagingInfo A reference PagingInfo instance. [default: null]
   * @return Array containing the ObjectId instances
   */
  function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * Get the first object id of objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @param $criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param $pagingInfo A reference PagingInfo instance. [default: null]
   * @return ObjectId or null
   */
  function getFirstOID($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * Load the objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) [default: BuildDepth::SINGLE]
   * @param $criteria An array of Criteria instances that define conditions on the object's attributes (maybe null). [default: null]
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param $pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @return Array containing the PersistentObject instances
   */
  function loadObjects($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * Load the first object matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) [default: BuildDepth::SINGLE]
   * @param $criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param $pagingInfo A reference PagingInfo instance. [default: null]
   * @return PersistentObject or null
   */
  function loadFirstObject($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * Get the current business transaction.
   * @note There is only one transaction instance at the same time.
   * @return Transaction
   */
  function getTransaction();

  /**
   * Get the PersistenceMapper for a given type. If no mapper for this type is defined the mapper for type '*' will be returned
   * @param $type The type of the object to get the PersistenceMapper for (either fully qualified or simple, if not ambiguous)
   * @return PersistenceMapper or null on error
   */
  function getMapper($type);

  /**
   * Explicitly set a PersistentMapper for a type
   * @param $type The type to set the mapper for (fully qualified)
   * @param $mapper PersistenceMapper instance
   */
  function setMapper($type, PersistenceMapper $mapper);

  /**
   * Set logging state for insert/update/delete actions.
   * @param $isLogging Boolean whether logging should be enabled or not
   */
  function setLogging($isLogging);

  /**
   * Check if the PersistenceMapper is logging.
   * @return Boolean whether the PersistenceMapper is logging.
   */
  function isLogging();
}
?>
