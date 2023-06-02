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

use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceOperation;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\output\OutputStrategy;

 /**
 * PersistenceMapper defines the interface for all mapper classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PersistenceMapper {

  /**
   * Set the OutputStrategy used for logging persistence actions.
   * @param OutputStrategy $logStrategy
   */
  public function setLogStrategy(OutputStrategy $logStrategy): void;

  /**
   * Get the entity type that this mapper handles.
   * @return string
   */
  public function getType(): string;

  /**
   * Get the display name of the type.
   * @param Message $message Message instance used for translation
   * @return string
   */
  public function getTypeDisplayName(Message $message): string;

  /**
   * Get the description of the type.
   * @param Message $message Message instance used for translation
   * @return string
   */
  public function getTypeDescription(Message $message): string;

  /**
   * Get the names of the primary key values.
   * @return array<string>
   */
  public function getPkNames(): array;

  /**
   * Get the relations for this type
   * @param string $hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations (default: 'all')
   * @return array<RelationDescription>
   */
  public function getRelations(string $hierarchyType='all'): array;

  /**
   * Check if a named relation is defined.
   * @param string $roleName The role name of the relation
   * @return bool
   */
  public function hasRelation(string $roleName): bool;

  /**
   * Get the definition for a relation
   * @param string $roleName The role name of the relation
   * @return RelationDescription or null if the relation does not exist
   */
  public function getRelation(string $roleName): ?RelationDescription;

  /**
   * Get the definitions for relations to a given type
   * @param string $type The type name
   * @return array<RelationDescription>
   */
  public function getRelationsByType(string $type): array;

  /**
   * PersistentObject values may be tagged with application specific tags.
   * This method gets the attributes belonging to the given tags.
   * @param array<string> $tags An array of tags that the attribute should match. Empty array means all attributes independent of the given matchMode (default: empty array)
   * @param string $matchMode One of 'all', 'none', 'any', defines how the attribute's tags should match the given tags (default: 'all')
   * @return array<AttributeDescription>
   */
  public function getAttributes(array $tags=[], string $matchMode='all'): array;

  /**
   * Check if a named attribute is defined.
   * @param string $name The attribute name
   * @return bool
   */
  public function hasAttribute(string $name): bool;

  /**
   * Get the definition for an attribute.
   * @param string $name The attribute name
   * @return AttributeDescription or null if the attribute does not exist
   */
  public function getAttribute(string $name): ?AttributeDescription;

  /**
   * Get the display name of the type.
   * @param string $name The attribute name
   * @param Message $message Message instance used for translation
   * @return string
   */
  public function getAttributeDisplayName(string $name, Message $message): string;

  /**
   * Get the description of the attribute.
   * @param string $name The attribute name
   * @param Message $message Message instance used for translation
   * @return string
   */
  public function getAttributeDescription(string $name, Message $message): string;

  /**
   * Get the references to other entities
   * @return array<ReferenceDescription>
   */
  public function getReferences(): array;

  /**
   * Get meta information on the mapped class.
   * @return array<string, mixed> of key value pairs
   */
  public function getProperties(): array;

  /**
   * Check if this type may be explicitly sorted by the user using a persistent
   * attribute which stores the order. The roleName parameter allows to ask
   * for the order with respect to a specific role.
   * @param string $roleName The role name of the relation (optional, default: _null_)
   * @return bool
   */
  public function isSortable(string $roleName=null): bool;

  /**
   * Get the persistent attribute that is used to store the order of the type
   * as explicitly defined by the user. The roleName parameter allows to ask
   * for the order with respect to a specific role.
   * @param string $roleName The role name of the relation (optional, default: _null_)
   * @return array{'sortType': string, 'sortFieldName': string, 'sortDirection': string, 'isSortKey': bool|null}
   */
  public function getSortkey(string $roleName=null): array;

  /**
   * Get the names of the type and attributes to order by default and the sort directions
   * (ASC or DESC). If the order may be established explicitly by the user, the
   * isSortkey value is true. The roleName parameter allows to ask
   * for the order with respect to a specific role.
   * In a many to many relation the attributes may not be contained in the mapped type,
   * so sortType may be different from the mapper type.
   * @param string $roleName The role name of the relation (optional, default: _null_)
   * @return array{'sortType': string, 'sortFieldName': string, 'sortDirection': string, 'isSortKey': bool|null}
   */
  public function getDefaultOrder(string $roleName=null): array;

  /**
   * Load a PersistentObject instance from the storage.
   * @note PersistentMapper implementations must call the PersistentObject::afterLoad()
   * lifecycle callcack on each loaded object and attach it to the current transaction using
   * the Transaction::attach() method.
   * @param ObjectId $oid The object id of the object to construct
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: _BuildDepth::SINGLE_)
   * @return PersistentObject or null if oid does not exist or a given condition prevents loading.
   */
  public function load(ObjectId $oid, int $buildDepth=BuildDepth::SINGLE): ?PersistentObject;

  /**
   * Construct a PersistentObject instance of a given type.
   * @note PersistentMapper implementations must call the PersistentObject::afterCreate()
   * lifecycle callcack on each created object.
   * @param string $type The type of object to build
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::INFINITE, BuildDepth::PROXIES_ONLY) (default: _BuildDepth::SINGLE_)
   * @return PersistentObject
   */
  public function create(string $type, int $buildDepth=BuildDepth::SINGLE): PersistentObject;

  /**
   * Save a PersistentObject instance.
   * @note PersistentMapper implementations must call the PersistentObject::beforeUpdate()/
   * PersistentObject::afterUpdate() or PersistentObject::beforeInsert()/
   * PersistentObject::afterInsert() lifecycle callbacks on each object depending
   * on it's state.
   * @param PersistentObject $object
   */
  public function save(PersistentObject $object): void;

  /**
   * Delete a PersistentObject instance.
   * @note PersistentMapper implementations must call the PersistentObject::beforeDelete()/
   * PersistentObject::afterDelete() lifecycle callbacks on each object.
   * @param PersistentObject $object
   * @return bool
   */
  public function delete(PersistentObject $object): bool;

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
   * Load the objects for the specified role. The implementation must check the navigability of
   * the relation and return null, if the requested direction is not navigable. The result
   * depends on the multiplicity of the relation (singlevalued or multivalued).
   * @param array<PersistentObject>|array<PersistentObjectProxy> $objects Array of PersistentObject or PersstentObjectProxy instances for which the related objects are loaded
   * @param string $role The role of the objects in relation to the given objects
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param array<Criteria> $criteria An array of Criteria instances that define conditions on the object's attributes (optional, default: _null_)
   * @param array<string> $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param PagingInfo $pagingInfo A reference PagingInfo instance (optional, default: _null_)
   * @return array<string, array<PersistentObject>> of object ids of the origin objects as keys and arrays of related
   * PersistentObject instances as values or null, if not navigable
   */
  public function loadRelation(array $objects, string $role, int $buildDepth=BuildDepth::SINGLE, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null): array;

  /**
   * Execute a PersistenceOperation. PersistenceOperation.type must be the type that
   * is mapped by this mapper.
   * @param PersistenceOperation $operation The operation
   * @return int The number of affected rows.
   */
  public function executeOperation(PersistenceOperation $operation): int;

  /**
   * Start a transaction on the transactional resource (e.g. database) of
   * this mapper. Nested transactions are not supported, so the implementation should
   * ignore consecutive calls, if a transaction is already started.
   */
  public function beginTransaction(): void;

  /**
   * Commit the transaction on the transactional resource (e.g. database) of
   * this mapper. The implementation should ignore calls, if no transaction
   * is started yet.
   */
  public function commitTransaction(): void;

  /**
   * Rollback the transaction on the transactional resource (e.g. database) of
   * this mapper. The implementation should ignore calls, if no transaction
   * is started yet.
   */
  public function rollbackTransaction(): void;

  /**
   * Get a list of all insert/update/delete statements that where executed in the last transaction.
   * @note Different mapper implementations may return different representations
   * @return array of statements
   */
  public function getStatements(): array;
}
?>
