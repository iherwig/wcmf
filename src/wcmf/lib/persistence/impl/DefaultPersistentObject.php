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
namespace wcmf\lib\persistence\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\NodeValueIterator;
use wcmf\lib\persistence\impl\NullMapper;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PropertyChangeEvent;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\StateChangeEvent;
use wcmf\lib\persistence\ValueChangeEvent;
use wcmf\lib\util\StringUtil;
use wcmf\lib\validation\ValidationException;
use wcmf\lib\validation\Validator;

/**
 * DefaultPersistentObject is the base class of all persistent objects.
 * It mainly implements an unique identifier for each instance (ObjectId),
 * tracking of the persistent state, methods for setting and getting values
 * as well as callback methods for lifecycle events.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultPersistentObject implements PersistentObject, \Serializable {

  private $oid = null;                // object identifier
  private $type = '';                 // the object type
  private $data = array();            // associative array holding the data
  private $properties = array();      // associative array holding the object properties
  private $valueProperties = array(); // associative array holding the value properties
  private $state = self::STATE_CLEAN; // the state of the PersistentObject
  private $changedAttributes = array(); // used to track changes, see setValue method
  private $originalData = array();    // data provided to the initialize method
  private $mapper = null;             // mapper instance

  private static $nullMapper = null;

  // TODO: add static cache for frequently requested entity type data

  /**
   * Constructor.
   * The object will be bound to the appropriate PersistenceMapper automatically,
   * if the PersistenceFacade knows the type. The object id is needed to extract
   * the type. If the id parameter of the object id is a dummy id, the object
   * is supposed to be a newly created object (@see ObjectId::containsDummyIds()).
   * @param $oid ObjectId instance (optional)
   * @param $initialData Associative array with initial attribute data to override default data (optional)
   */
  public function __construct(ObjectId $oid=null, array $initialData=null) {
    // set oid and state (avoid calling listeners)
    if ($oid == null || !ObjectId::isValid($oid)) {
      $oid = ObjectId::NULL_OID();
    }
    $this->type = $oid->getType();
    $this->oid = $oid;
    if ($oid->containsDummyIds()) {
      $this->state = self::STATE_NEW;
    }
    else {
      $this->state = self::STATE_CLEAN;
    }

    // get default data
    $data = array();
    $attributeDescriptions = $this->getMapper()->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc) {
      $data[$curAttributeDesc->getName()] = $curAttributeDesc->getDefaultValue();
    }

    // merge initial data
    if ($initialData != null) {
      $data = array_merge($data, $initialData);
    }

    // set data
    foreach ($data as $name => $value) {
      $this->setValueInternal($name, $value);
    }
    $this->originalData = $data;

    // set primary keys
    $this->setOIDInternal($oid, false);
  }

  /**
   * Initialize the PersistenceMapper instance
   */
  private function initializeMapper() {
    // set the mapper
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($persistenceFacade->isKnownType($this->type)) {
      $this->mapper = $persistenceFacade->getMapper($this->type);
    }
    else {
      // initialize null mapper if not done already
      if (self::$nullMapper == null) {
        self::$nullMapper = new NullMapper();
      }
      $this->mapper = self::$nullMapper;
    }
  }

  /**
   * @see PersistentObject::getType()
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @see PersistentObject::getMapper()
   */
  public function getMapper() {
    if ($this->mapper == null) {
      $this->initializeMapper();
    }
    return $this->mapper;
  }

  /**
   * @see PersistentObject::getOID()
   */
  public function getOID() {
    return $this->oid;
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
    $this->type = $oid->getType();
    $this->oid = $oid;
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
    return $this->state;
  }

  /**
   * @see PersistentObject::setState()
   */
  public function setState($state) {
    $oldState = $this->state;
    switch ($oldState) {
      // new object must stay new when it's modified
      case self::STATE_NEW:
        switch ($state) {
          case self::STATE_DIRTY:
            $this->state = self::STATE_NEW;
            break;

          default:
            $this->state = $state;
        }
        break;

        // deleted object must stay deleted in every case
      case self::STATE_DELETED:
        $this->state = self::STATE_DELETED;
        break;

      default:
        $this->state = $state;
    }
    if ($oldState != $this->state) {
      ObjectFactory::getInstance('eventManager')->dispatch(StateChangeEvent::NAME,
              new StateChangeEvent($this, $oldState, $this->state));
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
    $copy->oid = $this->oid;
    $copy->type = $this->type;
    $copy->data = $this->data;
    $copy->properties = $this->properties;
    $copy->valueProperties = $this->valueProperties;
    $copy->state = $this->state;

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
    $this->oid = new ObjectId($this->getType(), $pkValues);
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
    if (isset($this->data[$name])) {
      return $this->data[$name];
    }
    return null;
  }

  /**
   * @see PersistentObject::setValue()
   */
  public function setValue($name, $value, $forceSet=false, $trackChange=true) {
    if (!$forceSet) {
      $this->validateValue($name, $value);
    }
    $oldValue = self::getValue($name);
    if ($forceSet || $oldValue !== $value) {
      $this->setValueInternal($name, $value);
      if (in_array($name, $this->getMapper()->getPKNames())) {
        $this->updateOID();
      }
      if ($trackChange) {
        self::setState(self::STATE_DIRTY);
        $this->changedAttributes[$name] = true;
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
    $this->data[$name] = $value;
  }

  /**
   * @see PersistentObject::hasValue()
   */
  public function hasValue($name) {
    return array_key_exists($name, $this->data);
  }

  /**
   * @see PersistentObject::removeValue()
   */
  public function removeValue($name) {
    if ($this->hasValue($name)) {
      unset($this->data[$name]);
    }
  }

  /**
   * @see PersistentObject::validateValues()
   */
  public function validateValues() {
    $errorMessage = '';
    $iter = new NodeValueIterator($this, false);
    for($iter->rewind(); $iter->valid(); $iter->next()) {
      $curNode = $iter->currentNode();
      try {
        $curNode->validateValue($iter->key(), $iter->current());
      }
      catch (ValidationException $ex) {
        $errorMessage .= $ex->getMessage()."\n";
      }
    }
    if (strlen($errorMessage) > 0) {
      throw new ValidationException(null, null, $errorMessage);
    }
  }

  /**
   * @see PersistentObject::validateValue()
   */
  public function validateValue($name, $value) {
    $this->validateValueAgainstValidateType($name, $value);
  }

  /**
   * Check a value's value against the validation type set on it. This method uses the
   * validateType property of the attribute definition.
   * Throws a ValidationException if the value is not valid.
   * @param $name The name of the item to set.
   * @param $value The value of the item.
   */
  protected function validateValueAgainstValidateType($name, $value) {
    // don't validate referenced values
    $mapper = $this->getMapper();
    if ($mapper->hasAttribute($name) && $mapper->getAttribute($name) instanceof ReferenceDescription) {
      return;
    }
    $validateType = $this->getValueProperty($name, 'validate_type');
    if (($validateType == '' || Validator::validate($value, $validateType,
            array('entity' => $this, 'value' => $value)))) {
      return;
    }
    // construct the error message
    $errorMessage = ObjectFactory::getInstance('message')->getText("The value of '%0%' (%1%) is invalid.", array($name, $value));

    // use configured message if existing
    $validateDescription = $this->getValueProperty($name, 'validate_description');
    if (strlen($validateDescription) > 0) {
      $errorMessage .= " ".$validateDescription;
    }
    throw new ValidationException($name, $value, $errorMessage);
  }

  /**
   * @see PersistentObject::getChangedValues()
   */
  public function getChangedValues() {
    return array_keys($this->changedAttributes);
  }

  /**
   * @see PersistentObject::getOriginalValues()
   */
  public function getOriginalValues() {
    return $this->originalData;
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
    if (isset($this->properties[$name])) {
      return $this->properties[$name];
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
    $this->properties[$name] = $value;
    ObjectFactory::getInstance('eventManager')->dispatch(PropertyChangeEvent::NAME,
        new PropertyChangeEvent($this, $name, $oldValue, $value));
  }

  /**
   * @see PersistentObject::getPropertyNames()
   */
  public function getPropertyNames() {
    $result = array_keys($this->getMapper()->getProperties());
    return array_merge($result, array_keys($this->properties));
  }

  /**
   * @see PersistentObject::getValueProperty()
   */
  public function getValueProperty($name, $property) {
    if (!isset($this->valueProperties[$name]) || !isset($this->valueProperties[$name][$property])) {
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
      return $this->valueProperties[$name][$property];
    }
    return false;
  }

  /**
   * @see PersistentObject::setValueProperty()
   */
  public function setValueProperty($name, $property, $value) {
    if (!isset($this->valueProperties[$name])) {
      $this->valueProperties[$name] = array();
    }
    $this->valueProperties[$name][$property] = $value;
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
    return array_merge($result, array_keys($this->valueProperties[$name]));
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
      $names = array_keys($this->data);
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
    $this->mapper = null;
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