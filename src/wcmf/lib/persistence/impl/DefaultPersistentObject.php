<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\impl\NullMapper;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PropertyChangeEvent;
use wcmf\lib\persistence\StateChangeEvent;
use wcmf\lib\persistence\ValidationException;
use wcmf\lib\persistence\validator\Validator;
use wcmf\lib\persistence\ValueChangeEvent;
use wcmf\lib\util\StringUtil;

/**
 * DefaultPersistentObject is the base class of all persistent objects.
 * It mainly implements an unique identifier for each instance (ObjectId),
 * tracking of the persistent state, methods for setting and getting values
 * as well as callback methods for lifecycle events.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPersistentObject implements PersistentObject, \Serializable {

  private $_oid = null;                // object identifier
  private $_type = '';                 // the object type
  private $_data = array();            // associative array holding the data
  private $_properties = array();      // associative array holding the object properties
  private $_valueProperties = array(); // associative array holding the value properties
  private $_state = self::STATE_CLEAN; // the state of the PersistentObject
  private $_changedAttributes = array(); // used to track changes, see setValue method
  private $_originalData = array();    // data provided to the initialize method
  private $_mapper = null;             // mapper instance

  private static $_nullMapper = null;

  // TODO: add static cache for frequently requested entity type data

  /**
   * Constructor.
   * The object will be bound to the appropriate PersistenceMapper automatically,
   * if the PersistenceFacade knows the type. The object id is needed to extract
   * the type. If the id parameter of the object id is a dummy id, the object
   * is supposed to be a newly created object (@see ObjectId::containsDummyIds()).
   * @param $oid ObjectId instance (optional)
   */
  public function __construct(ObjectId $oid=null) {
    // set oid and state (avoid calling listeners)
    if ($oid == null || !ObjectId::isValid($oid)) {
      $oid = ObjectId::NULL_OID();
    }
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

  /**
   * @see PersistentObject::initialize()
   */
  public function initialize(array $data) {
    foreach ($data as $name => $value) {
      $this->setValueInternal($name, $value);
    }
    $this->_originalData = $data;
  }

  /**
   * Initialize the PersistenceMapper instance
   */
  private function initializeMapper() {
    // set the mapper
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($persistenceFacade->isKnownType($this->_type)) {
      $this->_mapper = $persistenceFacade->getMapper($this->_type);
    }
    else {
      // initialize null mapper if not done already
      if (self::$_nullMapper == null) {
        self::$_nullMapper = new NullMapper();
      }
      $this->_mapper = self::$_nullMapper;
    }
  }

  /**
   * @see PersistentObject::getType()
   */
  public function getType() {
    return $this->_type;
  }

  /**
   * @see PersistentObject::getMapper()
   */
  public function getMapper() {
    if ($this->_mapper == null) {
      $this->initializeMapper();
    }
    return $this->_mapper;
  }

  /**
   * @see PersistentObject::getOID()
   */
  public function getOID() {
    return $this->_oid;
  }

  /**
   * @see PersistentObject::setOID()
   */
  public function setOID(ObjectId $oid) {
    $this->setOIDInternal($oid, true);
  }

  /**
   * Set the object id of the PersistentObject.
   * @param $oid The PersistentObject's oid.
   * @param $triggerListeners Boolean, whether value CahngeListeners should be
   * notified or not
   */
  protected function setOIDInternal(ObjectId $oid, $triggerListeners) {
    $this->_type = $oid->getType();
    $this->_oid = $oid;
    // update the primary key attributes
    $ids = $oid->getId();
    $pkNames = $this->getMapper()->getPkNames();
    for ($i=0, $count=sizeof($pkNames); $i<$count; $i++) {
      if ($triggerListeners) {
        $this->setValue($pkNames[$i], $ids[$i], true);
      }
      else {
        $this->setValueInternal($pkNames[$i], $ids[$i]);
      }
    }
  }

  /**
   * @see PersistentObject::getState()
   */
  public function getState() {
    return $this->_state;
  }

  /**
   * @see PersistentObject::setState()
   */
  public function setState($state) {
    $oldState = $this->_state;
    switch ($oldState) {
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
   * @see PersistentObject::delete()
   */
  public function delete() {
    // delete the object (will be done in the transaction)
    $this->setState(self::STATE_DELETED);
  }

  /**
   * @see PersistentObject::__clone()
   */
  public function __clone() {
    $class = get_class($this);
    $copy = new $class;
    $copy->_oid = $this->_oid;
    $copy->_type = $this->_type;
    $copy->_data = $this->_data;
    $copy->_properties = $this->_properties;
    $copy->_valueProperties = $this->_valueProperties;
    $copy->_state = $this->_state;

    return $copy;
  }

  /**
   * @see PersistentObject::copyValues()
   */
  public function copyValues(PersistentObject $object, $copyPkValues=true) {
    $pkNames = $this->getMapper()->getPkNames();
    $iter = new NodeValueIterator($this, false);
    foreach($iter as $valueName => $value) {
      if (strlen($value) > 0 && ($copyPkValues || !in_array($valueName, $pkNames))) {
        $object->setValue($valueName, $value, true);
      }
    }
  }

  /**
   * @see PersistentObject::mergeValues()
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
   * @see PersistentObject::clearValues()
   */
  public function clearValues() {
    $pkNames = $this->getMapper()->getPkNames();
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
    $pkValues = array();
    // collect the values of the primary keys and compose the oid from them
    $pkNames = $this->getMapper()->getPkNames();
    foreach ($pkNames as $pkName) {
      $pkValues[] = self::getValue($pkName);
    }
    $this->_oid = new ObjectId($this->getType(), $pkValues);
  }

  /**
   * @see PersistentObject::afterCreate()
   * @note The default implementation does nothing
   */
  public function afterCreate() {}

  /**
   * @see PersistentObject::beforeInsert()
   * @note The default implementation does nothing
   */
  public function beforeInsert() {}

  /**
   * @see PersistentObject::afterInsert()
   * @note The default implementation does nothing
   */
  public function afterInsert() {}

  /**
   * @see PersistentObject::afterLoad()
   * @note The default implementation does nothing
   */
  public function afterLoad() {}

  /**
   * @see PersistentObject::beforeUpdate()
   * @note The default implementation does nothing
   */
  public function beforeUpdate() {}

  /**
   * @see PersistentObject::afterUpdate()
   * @note The default implementation does nothing
   */
  public function afterUpdate() {}

  /**
   * @see PersistentObject::beforeDelete()
   * @note The default implementation does nothing
   */
  public function beforeDelete() {}

  /**
   * @see PersistentObject::afterDelete()
   * @note The default implementation does nothing
   */
  public function afterDelete() {}

  /**
   * @see PersistentObject::getValue()
   */
  public function getValue($name) {
    if (isset($this->_data[$name])) {
      return $this->_data[$name];
    }
    return null;
  }

  /**
   * @see PersistentObject::setValue()
   */
  public function setValue($name, $value, $forceSet=false, $trackChange=true) {
    if (!$forceSet) {
      try {
        $message = ObjectFactory::getInstance('message');
        $this->validateValue($name, $value, $message);
      }
      catch(ValidationException $ex) {
        $msg = "Invalid value (".$value.") for ".$this->getOID().".".$name.": ".$ex->getMessage();
        throw new ValidationException($msg);
      }
    }
    $oldValue = self::getValue($name);
    if ($forceSet || $oldValue !== $value) {
      $this->setValueInternal($name, $value);
      if (in_array($name, $this->getMapper()->getPKNames())) {
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
   * @param $name The name of the value
   * @param $value The value
   */
  protected function setValueInternal($name, $value) {
    $this->_data[$name] = $value;
  }

  /**
   * @see PersistentObject::hasValue()
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->_data);
  }

  /**
   * @see PersistentObject::removeValue()
   */
  public function removeValue($name) {
    if ($this->hasValue($name)) {
      unset($this->_data[$name]);
    }
  }

  /**
   * @see PersistentObject::validateValues()
   */
  public function validateValues(Message $message) {
    $errorMessage = '';
    $iter = new NodeValueIterator($this, false);
    for($iter->rewind(); $iter->valid(); $iter->next()) {
      $curNode = $iter->currentNode();
      try {
        $curNode->validateValue($iter->key(), $iter->current(), $message);
      }
      catch (ValidationException $ex) {
        $errorMessage .= $ex->getMessage()."\n";
      }
    }
    if (strlen($errorMessage) > 0) {
      throw new ValidationException($errorMessage);
    }
  }

  /**
   * @see PersistentObject::validateValue()
   */
  public function validateValue($name, $value, Message $message) {
    $this->validateValueAgainstValidateType($name, $value, $message);
  }

  /**
   * Check a value's value against the validation type set on it. This method uses the
   * validateType property of the attribute definition.
   * Throws a ValidationException if the valud is not valid.
   * @param $name The name of the item to set.
   * @param $value The value of the item.
   * @param $message The Message instance used to provide translations.
   */
  protected function validateValueAgainstValidateType($name, $value, Message $message) {
    $validateType = $this->getValueProperty($name, 'validate_type');
    if (($validateType == '' || Validator::validate($value, $validateType, $message))) {
      return;
    }
    // construct the error message
    $errorMessage = $message->getText("Wrong value for %0% (%1%). ", array($name, $value));

    // use configured message if existing
    $validateDescription = $this->getValueProperty($name, 'validate_description');
    if (strlen($validateDescription) > 0) {
      $errorMessage .= $validateDescription;
    }
    else {
      // construct default message
      if (strlen($validateType) > 0) {
        $errorMessage .= $message->getText("The value must match %0%.", array($validateType));
      }
    }
    throw new ValidationException($errorMessage);
  }

  /**
   * @see PersistentObject::getChangedValues()
   */
  public function getChangedValues() {
    return array_keys($this->_changedAttributes);
  }

  /**
   * @see PersistentObject::getOriginalValues()
   */
  public function getOriginalValues() {
    return $this->_originalData;
  }

  /**
   * @see PersistentObject::getIndispensableObjects()
   */
  public function getIndispensableObjects() {
    return array();
  }

  /**
   * @see PersistentObject::getProperty()
   */
  public function getProperty($name) {
    if (isset($this->_properties[$name])) {
      return $this->_properties[$name];
    }
    else {
      // get the default property value from the mapper
      $properties = $this->getMapper()->getProperties();
      if (isset($properties[$name])) {
        return $properties[$name];
      }
    }
    return null;
  }

  /**
   * @see PersistentObject::setProperty()
   */
  public function setProperty($name, $value) {
    $oldValue = $this->getProperty($name);
    $this->_properties[$name] = $value;
    ObjectFactory::getInstance('eventManager')->dispatch(PropertyChangeEvent::NAME,
        new PropertyChangeEvent($this, $name, $oldValue, $value));
  }

  /**
   * @see PersistentObject::getPropertyNames()
   */
  public function getPropertyNames() {
    $result = array_keys($this->getMapper()->getProperties());
    return array_merge($result, array_keys($this->_properties));
  }

  /**
   * @see PersistentObject::getValueProperty()
   */
  public function getValueProperty($name, $property) {
    if (!isset($this->_valueProperties[$name]) || !isset($this->_valueProperties[$name][$property])) {
      // get the default property value from the mapper
      $mapper = $this->getMapper();
      if ($mapper->hasAttribute($name)) {
        $attr = $mapper->getAttribute($name);
        if ($attr) {
          $getter = 'get'.ucfirst(StringUtil::underScoreToCamelCase($property, true));
          return $attr->$getter();
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
   * @see PersistentObject::setValueProperty()
   */
  public function setValueProperty($name, $property, $value) {
    if (!isset($this->_valueProperties[$name])) {
      $this->_valueProperties[$name] = array();
    }
    $this->_valueProperties[$name][$property] = $value;
  }

  /**
   * @see PersistentObject::getValuePropertyNames()
   */
  public function getValuePropertyNames($name) {
    $result = array();
    $mapper = $this->getMapper();
    if ($mapper->hasAttribute($name)) {
      $attr = $mapper->getAttribute($name);
      if ($attr) {
        $result = $attr->getPropertyNames();
      }
    }
    return array_merge($result, array_keys($this->_valueProperties[$name]));
  }

  /**
   * @see PersistentObject::getValueNames()
   */
  public function getValueNames($excludeTransient=false) {
    if ($excludeTransient) {
      // get only value names from mapper
      $names = array();
      $attributes = $this->getMapper()->getAttributes();
      foreach ($attributes as $attribute) {
        $names[] = $attribute->getName();
      }
    }
    else {
      $names = array_keys($this->_data);
    }
    return $names;
  }

  /**
   * @see PersistentObject::getDisplayValue()
   * @note Subclasses will override this for special application requirements
   */
  public function getDisplayValue() {
    return $this->getOID()->__toString();
  }

  /**
   * @see PersistentObject::dump()
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
        if (is_object($value)) {
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
   * @param $array The array to dump
   * @return String
   */
  private static function dumpArray(array $array) {
    $str = "[";
    foreach ($array as $value) {
      if (is_object($value)) {
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

  public function serialize() {
    $this->_mapper = null;
    return serialize(get_object_vars($this));
  }

  public function unserialize($serialized) {
    $values = unserialize($serialized);
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }
}
?>