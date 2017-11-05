<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
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
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceOperation;
use wcmf\lib\persistence\PersistentObject;

 /**
 * PersistenceMapper defines the interface for all mapper classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PersistenceMapper {

  /**
   * Get the entity type that this mapper handles.
   * @return String
   */
  public function getType();

  /**
   * Get the display name of the type.
   * @param $message Message instance used for translation
   */
  public function getTypeDisplayName(Message $message);

  /**
   * Get the description of the type.
   * @param $message Message instance used for translation
   */
  public function getTypeDescription(Message $message);

  /**
   * Get the names of the primary key values.
   * @return Array with the value names.
   */
  public function getPkNames();

  /**
   * Get the relations for this type
   * @param $hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations (default: 'all')
   * @return Array of RelationDescription instances
   */
  public function getRelations($hierarchyType='all');

  /**
   * Check if a named relation is defined.
   * @param $roleName The role name of the relation
   * @return Boolean
   */
  public function hasRelation($roleName);

  /**
   * Get the definition for a relation
   * @param $roleName The role name of the relation
   * @return RelationDescription or null if the relation does not exist
   */
  public function getRelation($roleName);

  /**
   * Get the definitions for relations to a given type
   * @param $type The type name
   * @return Array of RelationDescription instances
   */
  public function getRelationsByType($type);

  /**
   * PersistentObject values may be tagged with application specific tags.
   * This method gets the attributes belonging to the given tags.
   * @param $tags An array of tags that the attribute should match. Empty array means all attributes independent of the given matchMode (default: empty array)
   * @param $matchMode One of 'all', 'none', 'any', defines how the attribute's tags should match the given tags (default: 'all')
   * @return Array of AttributeDescription instances
   */
  public function getAttributes(array $tags=[], $matchMode='all');

  /**
   * Check if a named attribute is defined.
   * @param $name The attribute name
   * @return Boolean
   */
  public function hasAttribute($name);

  /**
   * Get the definition for an attribute.
   * @param $name The attribute name
   * @return AttributeDescription or null if the attribute does not exist
   */
  public function getAttribute($name);

  /**
   * Get the display name of the type.
   * @param $name The attribute name
   * @param $message Message instance used for translation
   */
  public function getAttributeDisplayName($name, Message $message);

  /**
   * Get the description of the attribute.
   * @param $name The attribute name
   * @param $message Message instance used for translation
   */
  public function getAttributeDescription($name, Message $message);

  /**
   * Get the references to other entities
   * @return Array of ReferenceDescription instances
   */
  public function getReferences();

  /**
   * Get meta information on the mapped class.
   * @return Associative array of key value pairs
   */
  public function getProperties();

  /**
   * Check if this type may be explicitly sorted by the user using a persistent
   * attribute which stores the order. The roleName parameter allows to ask
   * for the order with respect to a specific role.
   * @param $roleName The role name of the relation (optional, default: _null_)
   * @return Boolean
   */
  public function isSortable($roleName=null);

  /**
   * Get the persistent attribute that is used to store the order of the type
   * as explicitly defined by the user. The roleName parameter allows to ask
   * for the order with respect to a specific role.
   * @param $roleName The role name of the relation (optional, default: _null_)
   * @return Assciative array with the keys sortType, sortFieldName, sortDirection
   * (ASC or DESC) and isSortkey (Boolean) or null, if the type is not sortable
   */
  public function getSortkey($roleName=null);

  /**
   * Get the names of the type and attributes to order by default and the sort directions
   * (ASC or DESC). If the order may be established explicitly by the user, the
   * isSortkey value is true. The roleName parameter allows to ask
   * for the order with respect to a specific role.
   * In a many to many relation the attributes may not be contained in the mapped type,
   * so sortType may be different from the mapper type.
   * @param $roleName The role name of the relation (optional, default: _null_)
   * @return An array of assciative arrays with the keys sortType, sortFieldName, sortDirection
   * (ASC or DESC) and isSortkey (Boolean)
   */
  public function getDefaultOrder($roleName=null);

  /**
   * Load a PersistentObject instance from the storage.
   * @note PersistentMapper implementations must call the PersistentObject::afterLoad()
   * lifecycle callcack on each loaded object and attach it to the current transaction using
   * the Transaction::attach() method.
   * @param $oid The object id of the object to construct
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: _BuildDepth::SINGLE_)
   * @return PersistentObject, null if oid does not exist or a given condition prevents loading.
   */
  public function load(ObjectId $oid, $buildDepth=BuildDepth::SINGLE);

  /**
   * Construct a PersistentObject instance of a given type.
   * @note PersistentMapper implementations must call the PersistentObject::afterCreate()
   * lifecycle callcack on each created object.
   * @param $type The type of object to build
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::INFINITE, BuildDepth::PROXIES_ONLY) (default: _BuildDepth::SINGLE_)
   * @return PersistentObject
   */
  public function create($type, $buildDepth=BuildDepth::SINGLE);

  /**
   * Save a PersistentObject instance.
   * @note PersistentMapper implementations must call the PersistentObject::beforeUpdate()/
   * PersistentObject::afterUpdate() or PersistentObject::beforeInsert()/
   * PersistentObject::afterInsert() lifecycle callcacks on each object depending
   * on it's state.
   * @param $object PersistentObject
   */
  public function save(PersistentObject $object);

  /**
   * Delete a PersistentObject instance.
   * @note PersistentMapper implementations must call the PersistentObject::beforeDelete()/
   * PersistentObject::afterDelete() lifecycle callcacks on each object.
   * @param $object PersistentObject
   */
  public function delete(PersistentObject $object);

  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null);

  /**
   * Load the objects for the specified role. The implementation must check the navigability of
   * the relation and return null, if the requested direction is not navigable. The result
   * depends on the multiplicity of the relation (singlevalued or multivalued).
   * @param $objects Array of PersistentObject or PersstentObjectProxy instances for which the related objects are loaded
   * @param $role The role of the objects in relation to the given objects
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param $criteria An array of Criteria instances that define conditions on the object's attributes (optional, default: _null_)
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param $pagingInfo A reference PagingInfo instance (optional, default: _null_)
   * @return Associative array with the object ids of the origin objects as keys and arrays of related
   * PersistentObject instances as values or null, if not navigable
   */
  public function loadRelation(array $objects, $role, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null);

  /**
   * Execute a PersistenceOperation. PersistenceOperation.type must be the type that
   * is mapped by this mapper.
   * @param $operation The operation
   * @return int The number of affected rows.
   */
  public function executeOperation(PersistenceOperation $operation);

  /**
   * Start a transaction on the transactional resource (e.g. database) of
   * this mapper. Nested transactions are not supported, so the implementation should
   * ignore consecutive calls, if a transaction is already started.
   */
  public function beginTransaction();

  /**
   * Commit the transaction on the transactional resource (e.g. database) of
   * this mapper. The implementation should ignore calls, if no transaction
   * is started yet.
   */
  public function commitTransaction();

  /**
   * Rollback the transaction on the transactional resource (e.g. database) of
   * this mapper. The implementation should ignore calls, if no transaction
   * is started yet.
   */
  public function rollbackTransaction();
}
?>
