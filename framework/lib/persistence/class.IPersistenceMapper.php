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

 /**
 * @interface IPersistenceMapper
 * @ingroup Persistence
 * @brief IPersistenceMapper defines the interface for all mapper classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface IPersistenceMapper
{
  /**
   * Get the mapper type.
   * @return String
   */
  public function getType();

  /**
   * Get the names of the primary key values.
   * @return Array with the value names.
   */
  public function getPkNames();

  /**
   * Add quotation to a given identifier (like column name).
   * @param identifier The identifier string
   * @return String
   */
  public function quoteIdentifier($identifier);

  /**
   * Add quotation to a given value.
   * @param value The value
   * @return String
   */
  public function quoteValue($value);

  /**
   * Get the relations for this type
   * @param hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations [default: 'all']
   * @return Array of RelationDescription instances
   */
  public function getRelations($hierarchyType='all');

  /**
   * Check if a named relation is defined.
   * @param rolename The role name of the relation
   * @return Boolean
   */
  public function hasRelation($roleName);

  /**
   * Get the definition for a relation
   * @param rolename The role name of the relation
   * @return RelationDescription or null if the relation does not exist
   */
  public function getRelation($roleName);

  /**
   * PersistentObject values may be tagged with application specific tags.
   * This method gets the attributes belonging to the given tags.
   * @param tags An array of tags that the attribute should match. Empty array means all attributes independent of the given matchMode [default: empty array]
   * @param matchMode One of 'all', 'none', 'any', defines how the attribute's tags should match the given tags [default: 'all']
   * @return Array of AttributeDescription instances
   */
  public function getAttributes(array $tags=array(), $matchMode='all');

  /**
   * Check if a named attribute is defined.
   * @param name The attribute name
   * @return Boolean
   */
  public function hasAttribute($name);

  /**
   * Get the definition for an attribute.
   * @param name The attribute name
   * @return AttributeDescription or null if the attribute does not exist
   */
  public function getAttribute($name);

  /**
   * Get meta information on the mapped class.
   * @return Associative array of key value pairs
   */
  public function getProperties();

  /**
   * Check if this type may be explicitly sorted by the user
   * using a persistent attribute which stores the order.
   * @return Boolean
   */
  public function isSortable();

  /**
   * Get the name of the attribute to order by default and the sort direction
   * (ASC or DESC). The roleName parameter allows to ask for the order with respect to a specific role.
   * In a many to many relation the attribute may not be contained in the mapped type.
   * @param rolename The role name of the relation, maybe null [default: null]
   * @return Assciative array with the keys type, sortFieldName and sortDirection (ASC or DESC)
   */
  public function getDefaultOrder($roleName=null);

  /**
   * Set the DataConverter that should be used on load() and save().
   * @param dataConverter The DataConverter object.
   */
  public function setDataConverter(DataConverter $dataConverter);

  /**
   * Get the DataConverter that is used on load() and save().
   * @return DataConverter
   */
  public function getDataConverter();

  /**
   * Enable logging using a given OutputStrategy to log insert/update/delete actions to a file.
   * @param logStrategy The OutputStrategy to use.
   */
  public function enableLogging(OutputStrategy $logStrategy);

  /**
   * Disable logging.
   */
  public function disableLogging();

  /**
   * Check if the PersistenceMapper is logging.
   * @return Boolean whether the PersistenceMapper is logging.
   */
  public function isLogging();

  /**
   * @see IPersistenceFacade::load()
   */
  public function load(ObjectId $oid, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null);

  /**
   * @see IPersistenceFacade::create()
   */
  public function create($type, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null);

  /**
   * @see IPersistenceFacade::save()
   */
  public function save(PersistentObject $object);

  /**
   * @see IPersistenceFacade::delete()
   */
  public function delete(ObjectId $oid);

  /**
   * @see IPersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * @see IPersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null,
    PagingInfo $pagingInfo=null, $buildAttribs=null, $buildTypes=null);

  /**
   * Load the objects of the specified role.
   * @param object The object for which the objects are loaded
   * @param role The role of the objects in relation to the given object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param buildAttribs An assoziative array listing the attributes to load [default: null, loads all attributes]
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include [default: null, loads all types]
   * @return Array of PersistentObject instances
   */
  public function loadRelation(PersistentObject $object, $role, $buildDepth=BUILDDEPTH_SINGLE,
    $buildAttribs=null, $buildTypes=null);

  /**
   * Load the objects of the own type that are related to a given object.
   * @param otherObjectProxy A PersistentObjectProxy for the object that the objects to load are related to
   * @param otherRole The role of the other object in relation to the objects to load
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param buildAttribs An assoziative array listing the attributes to load [default: null, loads all attributes]
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include [default: null, loads all types]
   * @return Array of PersistentObject instances
   */
  public function loadRelatedObjects(PersistentObjectProxy $otherObjectProxy, $otherRole, $buildDepth=BUILDDEPTH_SINGLE,
    $buildAttribs=null, $buildTypes=null);

  /**
   * Execute a PersistenceOperation. PersistenceOperation.type must be the type that
   * is mapped by this mapper.
   * @param operation The operation
   * @return int The number of affected rows.
   */
  public function executeOperation(PersistenceOperation $operation);

  /**
   * Start a transaction on the transactional resource (e.g. database) of
   * this mapper. Nested transactions are not supported, so the implementation should
   * ignore consecutive calls, if a transaction is already started.
   */
  public function startTransaction();

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
