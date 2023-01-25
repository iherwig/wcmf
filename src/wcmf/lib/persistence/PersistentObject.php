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
   * @return string
   */
  public function getType(): string;

  /**
   * Get the PersistenceMapper of the object.
   * @return PersistenceMapper
   */
  public function getMapper(): PersistenceMapper;

  /**
   * Get the object id of the PersistentObject.
   * @return ObjectId
   */
  public function getOID(): ObjectId;

  /**
   * Set the object id of the PersistentObject.
   * @param $oid The PersistentObject's oid.
   */
  public function setOID(ObjectId $oid): void;

  /**
   * Get the object's state (one of the STATE constant values)
   * @return int
   */
  public function getState(): int;

  /**
   * Set the state of the object to one of the STATE constants.
   * @param int The state
   */
  public function setState(int $state): void;

  /**
   * Delete the object
   */
  public function delete(): void;

  /**
   * Get a copy of the object (ChangeListeners and Lock are not copied)
   * @return self
   */
  public function __clone();

  /**
   * Copy all non-empty values to a given instance (ChangeListeners are triggered)
   * @param PersistentObject $object PersistentObject instance to copy the values to.
   * @param bool $copyPkValues Boolean whether primary key values should be copied
   */
  public function copyValues(PersistentObject $object, ?bool $copyPkValues=true): void;

  /**
   * Copy all values, that don't exist yet from a given instance
   * (ChangeListeners are not triggered)
   * @param PersistentObject $object PersistentObject instance to copy the values from.
   */
  public function mergeValues(PersistentObject $object): void;

  /**
   * Clear all values. Set each value to null except
   * for the primary key values
   */
  public function clearValues(): void;

  /**
   * Reset all values to their original values
   */
  public function reset(): void;

  /**
   * <!--
   * Persistence hook methods.
   * -->
   */

  /**
   * This method is called once after creation of this object. At this time it
   * is not known in the store.
   */
  public function afterCreate(): void;

  /**
   * This method is called once before inserting the newly created object into the store.
   */
  public function beforeInsert(): void;

  /**
   * This method is called once after inserting the newly created object into the store.
   */
  public function afterInsert(): void;

  /**
   * This method is called always after loading the object from the store.
   */
  public function afterLoad(): void;

  /**
   * This method is called always before updating the modified object in the store.
   */
  public function beforeUpdate(): void;

  /**
   * This method is called always after updating the modified object in the store.
   */
  public function afterUpdate(): void;

  /**
   * This method is called once before deleting the object from the store.
   */
  public function beforeDelete(): void;

  /**
   * This method is called once after deleting the object from the store.
   */
  public function afterDelete(): void;

  /**
   * <!--
   * Values and Properties
   * -->
   */

  /**
   * Get the value of an attribute.
   * @param string $name The name of the attribute.
   * @return mixed
   */
  public function getValue(string $name);

  /**
   * Set the value of an attribute if it exists.
   * @param string $name The name of the attribute to set.
   * @param mixed $value The value of the attribute.
   * @param bool $forceSet Boolean whether to set the value even if it is already set
   *   and to bypass validation (used to notify listeners) (default: _false_)
   * @param bool $trackChange Boolean whether to track the change (change state, notify listeners) or not (default: _true_)
   *      Only set this false, if you know, what you are doing
   * @return bool
   */
  public function setValue(string $name, $value, bool $forceSet=false, bool $trackChange=true): bool;

  /**
   * Check if the object has a given attribute.
   * @param string $name The name of the attribute.
   * @return bool
   */
  public function hasValue(string $name): bool;

  /**
   * Remove an attribute.
   * @param string $name The name of the attribute to remove.
   */
  public function removeValue(string $name): void;

  /**
   * Get the names of all attributes.
   * @param bool $excludeTransient Boolean whether to exclude transient values (default: _false_)
   * @return array
   */
  public function getValueNames(bool $excludeTransient=false): array;

  /**
   * Validate all values by calling PersistentObject::validateValue()
   * Throws a ValidationException in case of invalid data.
   */
  public function validateValues(): void;

  /**
   * Check if data may be set. The method is also called, when setting a value.
   * Controller may call this method before setting data and saving the object.
   * Throws a ValidationException in case of invalid data.
   * @param string $name The name of the attribute to set.
   * @param mixed $value The value of the attribute.
   * The default implementation calls PersistentObject::validateValueAgainstValidateType().
   */
  public function validateValue(string $name, $value): void;

  /**
   * Get the list of changed attributes since creation, loading.
   * @return array
   */
  public function getChangedValues(): array;

  /**
   * Get the original of an attribute provided to the initialize method.
   * @param string $name The name of the attribute.
   * @return mixed
   */
  public function getOriginalValue(string $name);

  /**
   * Get the list of objects that must exist in the store, before
   * this object may be persisted. Implementing classes may use this method to
   * manage dependencies.
   * @return array
   */
  public function getIndispensableObjects(): array;

  /**
   * Get the value of a named property in the object.
   * @param string $name The name of the property.
   * @return mixed
   */
  public function getProperty(string $name);

  /**
   * Set the value of a named property in the object.
   * @param string $name The name of the property to set.
   * @param mixed $value The value of the property to set.
   */
  public function setProperty(string $name, $value): void;

  /**
   * Get the names of all properties in the object. Properties are
   * either defined by using the PersistentObject::setProperty() method
   * or by the PersistentMapper.
   * @return array
   */
  public function getPropertyNames(): array;

  /**
   * Get the value of one property of an attribute.
   * @param string $name The name of the attribute to get its properties.
   * @param string $property The name of the property to get.
   * @return mixed
   */
  public function getValueProperty(string $name, string $property);

  /**
   * Set the value of one property of an attribute.
   * @param string $name The name of the attribute to set its properties.
   * @param string $property The name of the property to set.
   * @param mixed $value The value to set on the property.
   */
  public function setValueProperty(string $name, string $property, $value): void;

  /**
   * Get the names of all properties of a value in the object.
   * @return array
   */
  public function getValuePropertyNames(string $name): array;

  /**
   * <!--
   * Output
   * -->
   */

  /**
   * Get the value of the object used for display.
   * @return string
   */
  public function getDisplayValue(): string;

  /**
   * Get a string representation of the values of the PersistentObject.
   * @return string
   */
  public function dump(): string;
}
?>