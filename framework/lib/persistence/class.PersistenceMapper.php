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
 * @class PersistenceMapper
 * @ingroup Persistence
 * @brief PersistenceMapper defines the interface for all mapper classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PersistenceMapper
{
  /**
   * Get the mapper type.
   * @return String
   */
  public function getType();

  /**
   * Get the names of the primary key values.
   * @return An array with the value names.
   */
  public function getPkNames();

  /**
   * Add quotation to a given identifier (like column name).
   * @param identifier The identifier string
   * @return The quoted string
   */
  public function quoteIdentifier($identifier);

  /**
   * Add quotation to a given value.
   * @param value The value
   * @return The quoted string
   */
  public function quoteValue($value);

  /**
   * Get the relations for this type
   * @param hierarchyType The hierarchy type that the other type has in relation to this type
   *                      'parent', 'child', 'undefined' or 'all' to get all relations [default: 'all']
   * @return An array of RelationDescription instances
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
   * @return RelationDescription instance or null if the relation does not exist
   */
  public function getRelation($roleName);

  /**
   * PersistentObject values may be tagged with application specific tags.
   * This method gets the attributes belonging to the given tags.
   * @param tags An array of tags that the attribute should match. Empty array means all attributes independent of the given matchMode [default: empty array]
   * @param matchMode One of 'all', 'none', 'any', defines how the attribute's tags should match the given tags [default: 'all']
   * @return An array of AttributeDescription instances
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
   * @return AttributeDescription instance or null if the attribute does not exist
   */
  public function getAttribute($name);

  /**
   * Get meta information on the mapped class.
   * @return An associative array of key value pairs
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
   * @return An assciative array with the keys type, sortFieldName and sortDirection (ASC or DESC)
   */
  public function getDefaultOrder($roleName=null);

  /**
   * Set the DataConverter that should be used on load() and save().
   * @param dataConverter The DataConverter object.
   */
  public function setDataConverter(DataConverter $dataConverter);

  /**
   * Get the DataConverter that is used on load() and save().
   * @return The DataConverter object.
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
  public function load(ObjectId $oid, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null);

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
  public function create($type, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null);

  /**
   * Save an Object to the persistent storage.
   * @param object A reference to the object to safe
   * @return Boolean depending on success of operation
   */
  public function save(PersistentObject $object);

  /**
   * Delete an Object from the persistent storage.
   * @param oid The object id of the Object to delete
   * @param recursive True/False whether to physically delete it's children too [default: true]
   * @return Boolean depending on success of operation
   */
  public function delete(ObjectId $oid, $recursive=true);

  /**
   * @see PersistenceFacade::getOIDs()
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * @see PersistenceFacade::loadObjects()
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
   * @return An array of PersistentObject instances
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
   * @return An array of PersistentObject instances
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
   * @see PersistenceFacade::startTransaction()
   */
  public function startTransaction();

  /**
   * @see PersistenceFacade::commitTransaction()
   */
  public function commitTransaction();

  /**
   * @see PersistenceFacade::rollbackTransaction()
   */
  public function rollbackTransaction();
}
?>
