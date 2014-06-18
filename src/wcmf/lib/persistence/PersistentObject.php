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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObjectProxy;
use wcmf\lib\persistence\PropertyChangeEvent;
use wcmf\lib\persistence\StateChangeEvent;
use wcmf\lib\persistence\ValueChangeEvent;
use wcmf\lib\persistence\ValidationException;
use wcmf\lib\persistence\validator\Validator;
use wcmf\lib\util\StringUtil;

/**
 * PersistentObject is the base class of all persistent objects.
 * It implements the basic persistence methods (save(), delete())
 * which will be delegated to the PersistenceMapper class that constructed the object.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObject {

  const STATE_CLEAN = 0;
  const STATE_DIRTY = 1;
  const STATE_NEW = 2;
  const STATE_DELETED = 3;

  private $_oid = null;                // object identifier
  private $_type = '';                 // the object type
  private $_data = array();            // associative array holding the data
  private $_valueProperties = array(); // associative array holding the value properties
  private $_properties = array();      // associative array holding the object properties
  private $_state = self::STATE_CLEAN;       // the state of the PersistentObject
  private $_changedAttributes = array(); // used to track changes, see setValue method
  private $_originalData = array();    // data provided to the initialize method

  // TODO: add static cache for frequently requested entity type data

  /**
   * Constructor.
   * The object will be bound to the appropriate PersistenceMapper automatically,
   * if the PersistenceFacade knows the type. The object id is needed to extract
   * the type. If the id parameter of the object id is a dummy id, the object
   * is supposed to be a newly created object (@see ObjectId::containsDummyIds()).
   * @param oid ObjectId instance (optional)
   */
  public function __construct(ObjectId $oid=null) {
    // set oid and state (avoid calling listeners)
    if ($oid == null) {
      $oid = ObjectId::NULL_OID();
    }
    if (ObjectId::isValid($oid)) {
      $this->_type = $oid->getType();
      $this->_oid = $oid;
      if ($oid->containsDummyIds()) {
        $this->_state = self::STATE_NEW;
      }
      else {
        $this->_state = self::STATE_CLEAN;
      }
      // set primary keys
      $this->setOIDInternal($oid, false);
    }
  }

  /**
   * Initialize the object with a set of data. This method does not validate, does not
   * change the object's state and does not call any listeners. Any existing data will
   * be overwritten. The data will also be used as base line for tracking changes.
   * @param data An associative array with the data to set.
   */
  public function initialize(array $data) {
    foreach ($data as $name => $value) {
      $this->setValueInternal($name, $value);
    }
    $this->_originalData = $data;
  }

  /**
   * Get the type of the object.
   * @return The objects type.
   */
  public function getType() {
    return $this->_type;
  }

  /**
   * Get the object id of the PersistentObject.
   * @return ObjectId
   */
  public function getOID() {
    return $this->_oid;
  }

  /**
   * Set the object id of the PersistentObject.
   * @param oid The PersistentObject's oid.
   */
  public function setOID(ObjectId $oid) {
    $this->setOIDInternal($oid, true);
  }

  /**
   * Set the object id of the PersistentObject.
   * @param oid The PersistentObject's oid.
   * @param triggerListeners Boolean, whether value CahngeListeners should be
   * notified or not
   */
  protected function setOIDInternal(ObjectId $oid, $triggerListeners) {
    $this->_type = $oid->getType();
    $this->_oid = $oid;
    $mapper = $this->getMapper();
    if ($mapper != null) {
      // update the primary key attributes
      $ids = $oid->getId();
      $pkNames = $mapper->getPkNames();
      for ($i=0, $count=sizeof($pkNames); $i<$count; $i++) {
        if ($triggerListeners) {
          $this->setValue($pkNames[$i], $ids[$i], true);
        }
        else {
          $this->setValueInternal($pkNames[$i], $ids[$i]);
        }
      }
    }
  }

  /**
   * Get the PersistenceMapper of the object.
   * @return PersistenceMapper
   */
  public function getMapper() {
    $mapper = null;

    // set the mapper, if defined in PersistenceFacade
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($persistenceFacade->isKnownType($this->_type)) {
      $mapper = $persistenceFacade->getMapper($this->_type);
    }
    return $mapper;
  }

  /**
   * Get the primary key values
   * @return Array of value names
   */
  public function getPkNames() {
    $pkNames = array();
    $mapper = $this->getMapper();
    if ($mapper != null) {
      $pkNames = $mapper->getPkNames();
    }
    return $pkNames;
  }

  /**
   * Delete the object
   */
  public function delete() {
    // delete the object (will be done in the transaction)
    $this->setState(self::STATE_DELETED);
  }

  /**
   * Get the object's state:
   * @return One of the STATE constant values:
   */
  public function getState() {
    return $this->_state;
  }

  /**
   * Set the state of the object to one of the STATE constants.
   */
  public function setState($state) {
    $oldState = $this->_state;
    switch ($this->_state) {
      // new object must stay new when it's modified
      case self::STATE_NEW:
        switch ($state) {
          case self::STATE_DIRTY:
            $this->_state = self::STATE_NEW;
            break;

          default:
            $this->_state = $state;
        }
        break;

        // deleted object must stay deleted in every case
      case self::STATE_DELETED:
        $this->_state = self::STATE_DELETED;
        break;

      default:
        $this->_state = $state;
    }
    if ($oldState != $this->_state) {
      ObjectFactory::getInstance('eventManager')->dispatch(StateChangeEvent::NAME,
              new StateChangeEvent($this, $oldState, $this->_state));
    }
  }

  /**
   * Get a copy of the object (ChangeListeners and Lock are not copied)
   * @return PersistentObject
   */
  public function __clone() {
    $class = get_class($this);
    $copy = new $class;
    $copy->_oid = $this->_oid;
    $copy->_type = $this->_type;
    $copy->_data = $this->_data;
    $copy->_properties = $this->_properties;
    $copy->_state = $this->_state;

    return $copy;
  }

  /**
   * Copy all non-empty values to a given instance (ChangeListeners are triggered)
   * @param object PersistentObject instance to copy the values to.
   * @param copyPkValues Boolean whether primary key values should be copied
   */
  public function copyValues(PersistentObject $object, $copyPkValues=true) {
    $pkNames = $this->getPkNames();
    $iter = new NodeValueIterator($this, false);
    foreach($iter as $valueName => $value) {
      if (strlen($value) > 0 && ($copyPkValues || !in_array($valueName, $pkNames))) {
        $object->setValue($valueName, $value, true);
      }
    }
  }

  /**
   * Copy all values, that don't exist yet from a given instance
   * (ChangeListeners are not triggered)
   * @param object PersistentObject instance to copy the values from.
   */
  public function mergeValues(PersistentObject $object) {
    $iter = new NodeValueIterator($object, false);
    foreach($iter as $valueName => $value) {
      if (!$this->hasValue($valueName) && strlen($value) > 0) {
        $this->setValueInternal($valueName, $value);
      }
    }
  }

  /**
   * Clear all values. Set each value to null except
   * for the primary key values
   */
  public function clearValues() {
    $pkNames = $this->getPkNames();
    $iter = new NodeValueIterator($this, false);
    for($iter->rewind(); $iter->valid(); $iter->next()) {
      $valueName = $iter->key();
      if (!in_array($valueName, $pkNames)) {
        $curNode = $iter->currentNode();
        $curNode->setValue($valueName, null, true);
      }
    }
  }

  /**
   * Recalculate the object id
   */
  private function updateOID() {
    $mapper = $this->getMapper();
    if ($mapper != null) {
      $pkValues = array();
      // collect the values of the primary keys and compose the oid from them
      $pkNames = $mapper->getPkNames();
      foreach ($pkNames as $pkName) {
        $pkValues[] = self::getValue($pkName);
      }
      $this->_oid = new ObjectId($this->getType(), $pkValues);
    }
  }

  /**
   * Persistence hook methods.
   * Subclasses may override this to implement special application requirements.
   * The default implementations do nothing.
   */
  /**
   * This method is called once after creation of this object. At this time it
   * is not known in the store.
   */
  public function afterCreate() {}

  /**
   * This method is called once before inserting the newly created object into the store.
   */
  public function beforeInsert() {}

  /**
   * This method is called once after inserting the newly created object into the store.
   */
  public function afterInsert() {}

  /**
   * This method is called always after loading the object from the store.
   */
  public function afterLoad() {}

  /**
   * This method is called always before updating the modified object in the store.
   */
  public function beforeUpdate() {}

  /**
   * This method is called always after updating the modified object in the store.
   */
  public function afterUpdate() {}

  /**
   * This method is called once before deleting the object from the store.
   */
  public function beforeDelete() {}

  /**
   * This method is called once after deleting the object from the store.
   */
  public function afterDelete() {}

  /**
   * Values and Properties
   */

  /**
   * Check if the node has a given item.
   * @param name The name of the item to query.
   * @return Boolean whether the item exists or not.
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->_data);
  }

  /**
   * Get the value of a named item.
   * @param name The name of the item to query.
   * @return The value of the item / null if it doesn't exits.
   */
  public function getValue($name) {
    if (isset($this->_data[$name])) {
      return $this->_data[$name];
    }
    return null;
  }

  /**
   * Remove a named item.
   * @param name The name of the item to remove.
   */
  public function removeValue($name) {
    if ($this->hasValue($name)) {
      unset($this->_data[$name]);
    }
  }

  /**
   * Validate all values
   * @return Empty string if validation succeeded, an error string else. The default implementation returns the result of
   *        PersistentObject::validateValueAgainstValidateType().
   */
  public function validateValues() {
    $errorMsg = '';
    $iter = new NodeValueIterator($this, false);
    for($iter->rewind(); $iter->valid(); $iter->next()) {
      $curNode = $iter->currentNode();
      $error = $curNode->validateValue($iter->key(), $iter->current());
      if (strlen($error) > 0) {
        $errorMsg .= $error."\n";
      }
    }
    return $errorMsg;
  }

  /**
   * Check if data may be set. The method is also called, when setting a value.
   * Controller may call this method before setting data and saving the object.
   * Throws a ValidationException in case of invalid data.
   * @param name The name of the item to set.
   * @param value The value of the item.
   * The default implementation calls PersistentObject::validateValueAgainstValidateType().
   * @note Subclasses will override this method to implement special application requirements.
   */
  public function validateValue($name, $value) {
    $this->validateValueAgainstValidateType($name, $value);
  }

  /**
   * Check a value's value against the validation type set on it. This method uses the
   * validateType property of the attribute definition.
   * Throws a ValidationException if the valud is not valid.
   * @param name The name of the item to set.
   * @param value The value of the item.
   */
  protected function validateValueAgainstValidateType($name, $value) {
    $validateType = $this->getValueProperty($name, 'validate_type');
    if (($validateType == '' || Validator::validate($value, $validateType))) {
      return;
    }
    // construct the error message
    $errorMessage = Message::get("Wrong value for %0% (%1%). ", array($name, $value));

    // use configured message if existing
    $validateDescription = $this->getValueProperty($name, 'validate_description');
    if (strlen($validateDescription) > 0) {
      $errorMessage .= Message::get($validateDescription);
    }
    else {
      // construct default message
      if (strlen($validateType) > 0) {
        $errorMessage .= Message::get("The value must match %0%.", array($validateType));
      }
    }
    throw new ValidationException($errorMessage);
  }

  /**
   * Set the value of a named item if it exists.
   * @param name The name of the item to set.
   * @param value The value of the item.
   * @param forceSet Boolean whether to set the value even if it is already set
   *   and to bypass validation (used to notify listeners) [default: false]
   * @param trackChange Boolean whether to track the change (change state, notify listeners) or not [default: true]
   *      Only set this false, if you know, what you are doing
   * @return Boolean whether the operation succeeds or not
   */
  public function setValue($name, $value, $forceSet=false, $trackChange=true) {
    if (!$forceSet) {
      try {
        $this->validateValue($name, $value);
      }
      catch(ValidationException $ex) {
        $msg = "Invalid value (".$value.") for ".$this->getOID().".".$name.": ".$ex->getMessage();
        throw new ValidationException($msg);
      }
    }
    $oldValue = self::getValue($name);
    if ($forceSet || $oldValue !== $value) {
      $this->setValueInternal($name, $value);
      $mapper = $this->getMapper();
      if ($mapper != null && in_array($name, $mapper->getPKNames())) {
        $this->updateOID();
      }
      if ($trackChange) {
        self::setState(self::STATE_DIRTY);
        $this->_changedAttributes[$name] = true;
        ObjectFactory::getInstance('eventManager')->dispatch(ValueChangeEvent::NAME,
            new ValueChangeEvent($this, $name, $oldValue, $value));
      }
      return true;
    }
    return false;
  }

  /**
   * Internal (fast) version to set a value without any validation, state change,
   * listener notification etc.
   * @param name The name of the value
   * @param value The value
   */
  protected function setValueInternal($name, $value) {
    $this->_data[$name] = $value;
  }

  /**
   * Get the list of changed attributes since creation, loading.
   * @return Array of value names
   */
  public function getChangedValues() {
    return array_keys($this->_changedAttributes);
  }

  /**
   * Get the original data provided to the initialize method.
   * @return Associative array with value names as keys and values as values
   */
  public function getOriginalValues() {
    return $this->_originalData;
  }

  /**
   * Get the list of objects that must exist in the store, before
   * this object may be persisted. Subclasses may use this method to
   * manage dependencies.
   * @return Array of PersistentObject instances
   */
  public function getIndispensableObjects() {
    return array();
  }

  /**
   * Get the value of one property of a named item.
   * @param name The name of the item to get its properties.
   * @param property The name of the property to get.
   * @return The value property/null if not found.
   */
  public function getValueProperty($name, $property) {
    if (!isset($this->_valueProperties[$name]) || !isset($this->_valueProperties[$name][$property])) {
      // get the default property value from the mapper
      $mapper = $this->getMapper();
      if ($mapper) {
        if ($mapper->hasAttribute($name)) {
          $attr = $mapper->getAttribute($name);
          if ($attr) {
            $getter = 'get'.ucfirst(StringUtil::underScoreToCamelCase($property, true));
            return $attr->$getter();
          }
        }
      }
    }
    else {
      // the default property value is overidden
      return $this->_valueProperties[$name][$property];
    }
    return false;
  }

  /**
   * Set the value of one property of a named item.
   * @param name The name of the item to set its properties.
   * @param property The name of the property to set.
   * @param value The value to set on the property.
   */
  public function setValueProperty($name, $property, $value) {
    if (!isset($this->_valueProperties[$name])) {
      $this->_valueProperties[$name] = array();
    }
    $this->_valueProperties[$name][$property] = $value;
  }

  /**
   * Get the names of all properties of a value in the object.
   * @return An array consisting of the names.
   */
  public function getValuePropertyNames($name) {
    $result = array();
    $mapper = $this->getMapper();
    if ($mapper) {
      if ($mapper->hasAttribute($name)) {
        $attr = $mapper->getAttribute($name);
        if ($attr) {
          $result = $attr->getPropertyNames();
        }
      }
    }
    return array_merge($result, array_keys($this->_valueProperties[$name]));
  }

  /**
   * Get the names of all persistent items.
   * @return An array of item names.
   */
  public function getPersistentValueNames() {
    $names = array();
    $mapper = $this->getMapper();
    if ($mapper) {
      $attributes = $mapper->getAttributes();
      foreach ($attributes as $attribute) {
        $names[] = $attribute->getName();
      }
    }
    return $names;
  }

  /**
   * Get the names of all items.
   * @return An array of item names.
   */
  public function getValueNames() {
    $names = array_keys($this->_data);
    return $names;
  }

  /**
   * Check if the object contains all attributes described in it's mapper.
   * @return Boolean
   */
  public function isComplete() {
    $mapper = $this->getMapper();
    if ($mapper) {
      $attributes = $mapper->getAttributes();
      foreach ($attributes as $attribute) {
        if (!$this->hasValue($attribute->getName())) {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Get the value of a named property in the object.
   * @param name The name of the property to query.
   * @return The value of the property / null if it doesn't exits.
   */
  public function getProperty($name) {
    if (isset($this->_properties[$name])) {
      return $this->_properties[$name];
    }
    else {
      // get the default property value from the mapper
      $mapper = $this->getMapper();
      if ($mapper) {
        $properties = $mapper->getProperties();
        if (isset($properties[$name])) {
          return $properties[$name];
        }
      }
    }
    return null;
  }

  /**
   * Set the value of a named property in the object.
   * @param name The name of the property to set.
   * @param value The value of the property to set.
   */
  public function setProperty($name, $value) {
    $oldValue = $this->getProperty($name);
    $this->_properties[$name] = $value;
    ObjectFactory::getInstance('eventManager')->dispatch(PropertyChangeEvent::NAME,
        new PropertyChangeEvent($this, $name, $oldValue, $value));
  }

  /**
   * Get the names of all properties in the object. Properties are
   * either defined by using the PersistentObject::setProperty() method
   * or by the PersistentMapper.
   * @param excludeDefaultProperties Boolean whether to only return the
   *   properties defined by the PersistentObject::setProperty() method or
   *   also the properties defined by the mapper [default: false]
   * @return An array consisting of the names.
   */
  public function getPropertyNames($excludeDefaultProperties=false) {
    $result = array();
    if (!$excludeDefaultProperties) {
      $mapper = $this->getMapper();
      if ($mapper) {
        $result = array_keys($mapper->getProperties());
      }
    }
    return array_merge($result, array_keys($this->_properties));
  }

  /**
   * Output
   */

  /**
   * Get the name of the type used for display.
   * @return The name.
   * @note Sublasses will override this for special application requirements
   */
  public function getObjectDisplayName() {
    return Message::get($this->getType());
  }

  /**
   * Get the description of the type.
   * @return The description.
   * @note Sublasses will override this for special application requirements
   */
  public function getObjectDescription() {
    return Message::get($this->getType());
  }

  /**
   * Get the display value names of the object.
   * (defined by the property 'display_value')
   * @return An array of value names
   */
  public function getDisplayValueNames() {
    $displayValues = array();
    $displayValueStr = $this->getProperty('display_value');
    if (!strPos($displayValueStr, '|')) {
      $displayValues = array($displayValueStr);
    }
    else {
      $displayValues = preg_split('/\|/', $displayValueStr);
    }
    return $displayValues;
  }

  /**
   * Get the value of the object used for display.
   * @return The value.
   * @note Sublasses will override this for special application requirements
   */
  public function getDisplayValue() {
    return $this->getOID()->__toString();
  }

  /**
   * Get the name of a value used for display.
   * @param name The name of the value.
   * @param type The type of the value (not used by the default implementation) [default: null]
   * @return The name of the value.
   * @note Sublasses will override this for special application requirements
   */
  public function getValueDisplayName($name) {
    return Message::get($name);
  }

  /**
   * Get the description of a value.
   * @param name The name of the value.
   * @return The description of the value.
   * @note Sublasses will override this for special application requirements
   */
  public function getValueDescription($name) {
    return Message::get($name);
  }

  /**
   * Get a string representation of the values of the PersistentObject.
   * @return String
   */
  public function dump() {
    $str = $this->getOID()->__toString()."\n";
    foreach ($this->getValueNames() as $valueName) {
      $value = self::getValue($valueName);
      $valueStr = null;
      if (is_array($value)) {
        $valueStr = self::dumpArray($value)."\n";
      }
      else {
        if ($value instanceof PersistentObject || $value instanceof PersistentObjectProxy) {
          $valueStr = $value->__toString()."\n";
        }
        else {
          $valueStr = StringUtil::getDump($value);
        }
      }
      $str .= "  ".$valueName.": ".$valueStr;
    }
    return $str;
  }

  /**
   * Get a string representation of an array of values.
   * @param array The array to dump
   * @return String
   */
  private static function dumpArray(array $array) {
    $str = "[";
    foreach ($array as $value) {
      if ($value instanceof PersistentObject || $value instanceof PersistentObjectProxy) {
        $str .= $value->__toString().", ";
      }
      else {
        $str .= $value.", ";
      }
    }
    $str = preg_replace("/, $/", "", $str)."]";
    return $str;
  }

  /**
   * Get a string representation of the PersistentObject.
   * @return String
   */
  public function __toString() {
    return self::getDisplayValue();
  }
}
?>