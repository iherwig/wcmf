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

use wcmf\lib\persistence\ObjectId;

/**
 * PersistentObject defines the interface of all persistent objects.
 * It mainly requires an unique identifier for each instance (ObjectId),
 * tracking of the persistent state, methods for setting and getting values
 * as well as callback methods for lifecycle events.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PersistentObject {

  const STATE_CLEAN = 0;
  const STATE_DIRTY = 1;
  const STATE_NEW = 2;
  const STATE_DELETED = 3;

  /**
   * Get the type of the object.
   * @return The objects type.
   */
  public function getType();

  /**
   * Get the PersistenceMapper of the object.
   * @return PersistenceMapper
   */
  public function getMapper();

  /**
   * Get the object id of the PersistentObject.
   * @return ObjectId
   */
  public function getOID();

  /**
   * Set the object id of the PersistentObject.
   * @param $oid The PersistentObject's oid.
   */
  public function setOID(ObjectId $oid);

  /**
   * Get the object's state:
   * @return One of the STATE constant values:
   */
  public function getState();

  /**
   * Set the state of the object to one of the STATE constants.
   */
  public function setState($state);

  /**
   * Delete the object
   */
  public function delete();

  /**
   * Get a copy of the object (ChangeListeners and Lock are not copied)
   * @return PersistentObject
   */
  public function __clone();

  /**
   * Copy all non-empty values to a given instance (ChangeListeners are triggered)
   * @param $object PersistentObject instance to copy the values to.
   * @param $copyPkValues Boolean whether primary key values should be copied
   */
  public function copyValues(PersistentObject $object, $copyPkValues=true);

  /**
   * Copy all values, that don't exist yet from a given instance
   * (ChangeListeners are not triggered)
   * @param $object PersistentObject instance to copy the values from.
   */
  public function mergeValues(PersistentObject $object);

  /**
   * Clear all values. Set each value to null except
   * for the primary key values
   */
  public function clearValues();

  /**
   * <!--
   * Persistence hook methods.
   * -->
   */

  /**
   * This method is called once after creation of this object. At this time it
   * is not known in the store.
   */
  public function afterCreate();

  /**
   * This method is called once before inserting the newly created object into the store.
   */
  public function beforeInsert();

  /**
   * This method is called once after inserting the newly created object into the store.
   */
  public function afterInsert();

  /**
   * This method is called always after loading the object from the store.
   */
  public function afterLoad();

  /**
   * This method is called always before updating the modified object in the store.
   */
  public function beforeUpdate();

  /**
   * This method is called always after updating the modified object in the store.
   */
  public function afterUpdate();

  /**
   * This method is called once before deleting the object from the store.
   */
  public function beforeDelete();

  /**
   * This method is called once after deleting the object from the store.
   */
  public function afterDelete();

  /**
   * <!--
   * Values and Properties
   * -->
   */

  /**
   * Get the value of an attribute.
   * @param $name The name of the attribute.
   * @return The value of the attribute / null if it doesn't exist.
   */
  public function getValue($name);

  /**
   * Set the value of an attribute if it exists.
   * @param $name The name of the attribute to set.
   * @param $value The value of the attribute.
   * @param $forceSet Boolean whether to set the value even if it is already set
   *   and to bypass validation (used to notify listeners) (default: _false_)
   * @param $trackChange Boolean whether to track the change (change state, notify listeners) or not (default: _true_)
   *      Only set this false, if you know, what you are doing
   * @return Boolean whether the operation succeeds or not
   */
  public function setValue($name, $value, $forceSet=false, $trackChange=true);

  /**
   * Check if the object has a given attribute.
   * @param $name The name of the attribute.
   * @return Boolean whether the attribute exists or not.
   */
  public function hasValue($name);

  /**
   * Remove an attribute.
   * @param $name The name of the attribute to remove.
   */
  public function removeValue($name);

  /**
   * Get the names of all attributes.
   * @param $excludeTransient Boolean whether to exclude transient values (default: _false_)
   * @return An array of attribute names.
   */
  public function getValueNames($excludeTransient=false);

  /**
   * Validate all values by calling PersistentObject::validateValue()
   * Throws a ValidationException in case of invalid data.
   */
  public function validateValues();

  /**
   * Check if data may be set. The method is also called, when setting a value.
   * Controller may call this method before setting data and saving the object.
   * Throws a ValidationException in case of invalid data.
   * @param $name The name of the attribute to set.
   * @param $value The value of the attribute.
   * The default implementation calls PersistentObject::validateValueAgainstValidateType().
   */
  public function validateValue($name, $value);

  /**
   * Get the list of changed attributes since creation, loading.
   * @return Array of value names
   */
  public function getChangedValues();

  /**
   * Get the original of an attribute provided to the initialize method.
   * @param $name The name of the attribute.
   * @return The value of the attribute / null if it doesn't exist.
   */
  public function getOriginalValue($name);

  /**
   * Get the list of objects that must exist in the store, before
   * this object may be persisted. Implementing classes may use this method to
   * manage dependencies.
   * @return Array of PersistentObject instances
   */
  public function getIndispensableObjects();

  /**
   * Get the value of a named property in the object.
   * @param $name The name of the property.
   * @return The value of the property / null if it doesn't exist.
   */
  public function getProperty($name);

  /**
   * Set the value of a named property in the object.
   * @param $name The name of the property to set.
   * @param $value The value of the property to set.
   */
  public function setProperty($name, $value);

  /**
   * Get the names of all properties in the object. Properties are
   * either defined by using the PersistentObject::setProperty() method
   * or by the PersistentMapper.
   * @return An array consisting of the names.
   */
  public function getPropertyNames();

  /**
   * Get the value of one property of an attribute.
   * @param $name The name of the attribute to get its properties.
   * @param $property The name of the property to get.
   * @return The value property/null if not found.
   */
  public function getValueProperty($name, $property);

  /**
   * Set the value of one property of an attribute.
   * @param $name The name of the attribute to set its properties.
   * @param $property The name of the property to set.
   * @param $value The value to set on the property.
   */
  public function setValueProperty($name, $property, $value);

  /**
   * Get the names of all properties of a value in the object.
   * @return An array consisting of the names.
   */
  public function getValuePropertyNames($name);

  /**
   * <!--
   * Output
   * -->
   */

  /**
   * Get the value of the object used for display.
   * @return The value.
   */
  public function getDisplayValue();

  /**
   * Get a string representation of the values of the PersistentObject.
   * @return String
   */
  public function dump();
}
?>