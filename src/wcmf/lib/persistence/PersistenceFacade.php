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
   * @return array<string> list of fully qualified type names
   */
  public function getKnownTypes(): array;

  /**
   * Check if a type is defined in the application.
   * @param string $type The type to check (either fully qualified or simple, if not ambiguous)
   * @return bool whether the type is defined or not
   */
  public function isKnownType(string $type): bool;

  /**
   * Get the fully qualified type name for a given simple type name.
   * @param string $type Type name without namespace
   * @return string Fully qualified type name (with namespace)
   */
  public function getFullyQualifiedType(string $type): string;

  /**
   * Get the simple type name for a given fully qualified type name.
   * @param string $type Type name with namespace
   * @return string Simple type name (without namespace)
   */
  public function getSimpleType(string $type): string;

  /**
   * Load an object from the storage. The object will be attached to the transaction,
   * if the transaction is active.
   * @note The parameter buildDepth is used to improve fetching from the storage, but objects
   * returned by this method are not guaranteed to only contain the parameter values.
   * This is especially true, if the same object was loaded before with a wider fetch definition
   * (e.g. greater buildDeph value)
   * @param ObjectId $oid The object id of the object to construct
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: _BuildDepth::SINGLE_)
   * @return PersistentObject or null if oid does not exist or a given condition prevents loading.
   */
  public function load(ObjectId $oid, int $buildDepth=BuildDepth::SINGLE): ?PersistentObject;

  /**
   * Construct the template of an object of a given type. The object will be
   * attached to the transaction, if the transaction is active.
   * @note If an object required to be transient, the IPersistentMapper::create() method or the class
   * constructor must be used.
   * @param string $type The type of object to build (either fully qualified or simple, if not ambiguous)
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::INFINITE, BuildDepth::PROXIES_ONLY) (default: _BuildDepth::SINGLE_)
   * @return PersistentObject
   */
  public function create(string $type, int $buildDepth=BuildDepth::SINGLE): PersistentObject;

  /**
   * Get the object id of the last created object of a given type.
   * @param string $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @return ObjectId or null
   */
  public function getLastCreatedOID(string $type): ?ObjectId;

  /**
   * Get the object ids of objects matching a given criteria. If a PagingInfo instance is passed it will be used and updated.
   * @param string $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @param array<Criteria> $criteria An array of Criteria instances that define conditions on the type's attributes (optional, default: _null_)
   * @param array<string> $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param PagingInfo $pagingInfo A reference PagingInfo instance. (default: _null_)
   * @return array<ObjectId> containing the ObjectId instances
   */
  public function getOIDs(string $type, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): array;

  /**
   * Get the first object id of objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param string $type The type of the object (either fully qualified or simple, if not ambiguous)
   * @param array<Criteria> $criteria An array of Criteria instances that define conditions on the type's attributes (optional, default: _null_)
   * @param array<string> $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param PagingInfo $pagingInfo A reference PagingInfo instance. (default: _null_)
   * @return ObjectId or null
   */
  public function getFirstOID(string $type, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): ?ObjectId;

  /**
   * Load the objects matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param string|array<string> $typeOrTypes The type or types array of objects (either fully qualified or simple, if not ambiguous)
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param array<Criteria> $criteria An array of Criteria instances that define conditions on the object's attributes (optional, default: _null_)
   * @param array<string> $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param PagingInfo $pagingInfo A reference PagingInfo instance (optional, default: _null_)
   * @return array<PersistentObject>
   */
  public function loadObjects($typeOrTypes, int $buildDepth=BuildDepth::SINGLE, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): array;

  /**
   * Load the first object matching a given condition. If a PagingInfo instance is passed it will be used and updated.
   * @param string|array<string> $typeOrTypes The type or types array of objects (either fully qualified or simple, if not ambiguous)
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param array<Criteria> $criteria An array of Criteria instances that define conditions on the type's attributes (optional, default: _null_)
   * @param array<string> $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param PagingInfo $pagingInfo A reference PagingInfo instance (default: _null_)
   * @return PersistentObject or null
   */
  public function loadFirstObject($typeOrTypes, int $buildDepth=BuildDepth::SINGLE, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): ?PersistentObject;

  /**
   * Get the current business transaction.
   * @note There is only one transaction instance at the same time.
   * @return Transaction
   */
  public function getTransaction(): Transaction;

  /**
   * Get the PersistenceMapper for a given type. If no mapper for this type is defined the mapper for type '*' will be returned
   * @param string $type The type of the object to get the PersistenceMapper for (either fully qualified or simple, if not ambiguous)
   * @return PersistenceMapper or null on error
   */
  public function getMapper(string $type): ?PersistenceMapper;

  /**
   * Explicitly set a PersistentMapper for a type
   * @param string $type The type to set the mapper for (fully qualified)
   * @param PersistenceMapper $mapper PersistenceMapper instance
   */
  public function setMapper(string $type, PersistenceMapper $mapper): void;
}
?>
