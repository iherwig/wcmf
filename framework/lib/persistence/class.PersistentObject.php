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
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceMapper.php");
require_once(BASE."wcmf/lib/persistence/class.LockManager.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceException.php");
require_once(BASE."wcmf/lib/persistence/class.ValidationException.php");
require_once(BASE."wcmf/lib/util/class.SearchUtil.php");
require_once(BASE."wcmf/lib/util/class.EncodingUtil.php");
require_once(BASE."wcmf/lib/util/class.JSONUtil.php");

/**
 * Some constants describing the state of the PersistentObject
 */
define("STATE_CLEAN",   0);
define("STATE_DIRTY",   1);
define("STATE_NEW",     2);
define("STATE_DELETED", 3);

/**
 * @class PersistentObject
 * @ingroup Persistence
 * @brief PersistentObject is the base class of all persistent objects.
 * It implements the basic persistence methods (save(), delete())
 * which will be delegated to the PersistenceMapper class that contsructed the object.
 * The PersistentObject holds the object data in an associative array of the following structure:
 * @verbatim
 * valueName1---value
 *              properties---propertyName1---value
 *                                       propertyName2---value
 *                                       ...
 * valueName2---value
 *              properties---propertyName1---value
 *                                       ...
 * ...
 *
 * e.g.: $this->_data['name']['value']                 gives the value of the attribute 'name'
 *       $this->_data['name']['properties']['visible'] gives the value of the visibility property
 *                                                     of the attribute 'name'
 * @endverbatim
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistentObject
{
  private $_oid = null;                // object identifier
  private $_type = '';                 // the object type
  private $_data = array();            // associative array holding the data
  private $_properties = array();      // associative array holding the properties
  private $_state = STATE_CLEAN;       // the state of the PersistentObject
  private $_isImmutable = false;       // immutable state
  private $_changeListeners = array(); // the change listeners

  /**
   * Constructor. The object will be bound to the appripriate PersistenceMapper automatically, if the
   * the PersistenceFacade knows the type.
   * @param type The object type.
   * @param oid The object id (, optional will be calculated if not given or not valid).
   */
  public function __construct($type, ObjectId $oid=null)
  {
    $this->_type = $type;

    // set oid and state
    if (!(isset($oid)) || !ObjectId::isValid($oid))
    {
      // no oid is given -> new node
      $this->setOID(new ObjectId($type));
      $this->setState(STATE_NEW);
    }
    else
    {
      // old node
      $this->setOID($oid);
      $this->setState(STATE_CLEAN);
    }
  }
  /**
   * Get the type of the object.
   * @return The objects type.
   */
  public function getType()
  {
    return $this->_type;
  }
  /**
   * Set the type of the object.
   * @param type The objects type.
   */
  public function setType($type)
  {
    $this->_type = $type;
  }
  /**
   * Get the object id of the PersistentObject.
   * @return The PersistentObject's ObjectId.
   */
  public function getOID()
  {
    return $this->_oid;
  }
  /**
   * Set the object id of the PersistentObject.
   * @param oid The PersistentObject's oid.
   */
  public function setOID(ObjectId $oid)
  {
    $mapper = $this->getMapper();
    if ($mapper != null)
    {
      // update the primary key columns
      $ids = $oid->getId();
      $pkNames = $mapper->getPkNames();
      for ($i=0, $count=sizeof($pkNames); $i<$count; $i++) {
        $this->setValue($pkNames[$i], $ids[$i], true);
      }
    }
    // set this afterwards, because setValue may have triggered updateOID
    $this->_oid = $oid;
  }
  /**
   * Get the PersistenceMapper of the object.
   * @return A reference to a PersistenceMapper class
   */
  public function getMapper()
  {
    $mapper = null;

    // set the mapper, if defined in PersistenceFacade
    $persistenceFacade = PersistenceFacade::getInstance();
    if ($persistenceFacade->isKnownType($this->_type)) {
      $mapper = $persistenceFacade->getMapper($this->_type);
    }
    return $mapper;
  }
  /**
   * Get the DataConverter used when loading/saving values.
   * @return A reference to the dataConverter instance
   */
  public function getDataConverter()
  {
    $mapper = $this->getMapper();
    if ($mapper != null) {
      return $mapper->getDataConverter();
    }
    return null;
  }
  /**
   * Save data. This call will be delegated to the PersistenceMapper class.
   */
  public function save()
  {
    if (!$this->_isImmutable)
    {
      $mapper = $this->getMapper();
      if ($mapper != null)
      {
        $oldState = $this->getState();
        // call before hook method
        if ($oldState == STATE_NEW) {
          $this->beforeInsert();
        }
        elseif ($oldState == STATE_DIRTY) {
          $this->beforeUpdate();
        }
        // save the object
        $mapper->save($this);

        // update search index
        $this->indexInSearch();

        // call after hook method
        if ($oldState == STATE_NEW) {
          $this->afterInsert();
        }
        elseif ($oldState == STATE_DIRTY) {
          $this->afterUpdate();
        }
      }
    }
    else {
      throw new PersistenceException(Message::get("Cannot save immutable object '%1%'.", array($this->getOID())));
    }
  }
  /**
   * Delete data. This call will be delegated to the PersistenceMapper class.
   * @param recursive True/False whether to physically delete it's children too [default: true]
   */
  public function delete($recursive=true)
  {
    if (!$this->_isImmutable)
    {
      $mapper = $this->getMapper();
      if ($mapper != null)
      {
        // call before hook method
        $this->beforeDelete();

        // delete the object
        $mapper->delete($this->getOID(), $recursive);

        // remove from index
        $this->deleteFromSearchIndex();

        // call after hook method
        $this->afterDelete();
      }
    }
    else {
      throw new PersistenceException(Message::get("Cannot delete immutable object '%1%'.", array($this->getOID())));
    }
  }
  /**
   * Get the object's state:
   * @return One of the STATE constant values:
   */
  public function getState()
  {
    return $this->_state;
  }
  /**
   * Set the state of the object to one of the STATE constants.
   * @param recursive True/False [Default: True]
   * @note PersistentObject ignores the recursive parameter, but subclasses may use it
   */
  public function setState($state, $recursive=true)
  {
    $oldState = $this->_state;
    switch ($this->_state)
    {
      // new object must stay new when it's modified
      case STATE_NEW:
        switch ($state)
        {
          case STATE_DIRTY:
            $this->_state = STATE_NEW;
            break;

          default:
            $this->_state = $state;
        }
        break;

        // deleted object must stay deleted in every case
      case STATE_DELETED:
        $this->_state = STATE_DELETED;
        break;

      default:
        $this->_state = $state;
    }
    if ($oldState != $this->_state) {
      $this->propagateStateChange($oldState, $this->_state);
    }
  }
  /**
   * Set object immutable. Sets the editable property of each value to false.
   * and disables save/delete methods.
   * @note This operation is not reversible (reload the object to get a mutable one)
   */
  public function setImmutable()
  {
    // set editable attribute of all values to false
    foreach($this->getValueNames() as $name) {
      $this->setValueProperty($name, 'is_editable', false);
    }
    $this->_isImmutable = true;
  }
  /**
   * Get the lock on the object.
   * @return lock The lock as provided by LockManager::getLock() or null if not locked
   * @note If the object is locked it's set immutable. This is not reversible
   * (reload the object to get a mutable one)
   */
  public function getLock()
  {
    $lockManager = LockManager::getInstance();
    $lock = $lockManager->getLock($this->getOID());
    if ($lock != null) {
      $this->setImmutable();
    }
    return $lock;
  }
  /**
   * Get a copy of the object (ChangeListeners and Lock are not copied)
   * @return A reference to copy.
   */
  public function duplicate()
  {
    $class = get_class($this);
    $copy = new $class;
    $copy->_oid = $this->_oid;
    $copy->_type = $this->_type;
    $copy->_data = $this->_data;
    $copy->_properties = $this->_properties;
    $copy->_state = $this->_state;
    $copy->_isImmutable = $this->_isImmutable;

    return $copy;
  }
  /**
   * Copy all non-empty values to a given instance (ChangeListeners are triggered)
   * @param object A reference to the PersistentObject to copy the values to.
   * @param dataTypes An array of datatypes. Only values of that datatypes will be copied.
   * Empty array means all datatypes [default:empty array]
   * @param copyPkValues True/False wether primary key values should be copied if their
   * datatype is included or not [default: true]
   */
  public function copyValues(PersistentObject $object, array $dataTypes=array(), $copyPkValues=true)
  {
    $valuesToIgnore = array();
    $mapper = $this->getMapper();
    if ($mapper)
    {
      if (!$copyPkValues) {
        $valuesToIgnore = $mapper->getPkNames();
      }
      if (sizeof($dataTypes) > 0)
      {
        $attributesToCopy = $mapper->getAttributes($dataTypes);
        $valuesToCopy = array();
        foreach ($attributesToCopy as $attribute) {
          $valuesToCopy[] = $attribute->name();
        }
        $valuesToIgnore = array_diff($this->getValueNames(), $valuesToCopy);
      }
    }
    $processor = new NodeProcessor('copyValueIntern', array($object, $valuesToIgnore), $this);
    $processor->run($this, false);
  }
  /**
   * Private callback for copying values
   * @param valuesToIgnore An associative array with the value names as keys and the types as values
   * @see NodeProcessor
   */
  private function copyValueIntern(Node $node, $valueName, PersistentObject $targetNode, array $valuesToIgnore)
  {
    if (!in_array($valueName, $valuesToIgnore))
    {
      $value = $node->getValue($valueName);
      if (strlen($value) > 0) {
        $targetNode->setValue($valueName, $value, true);
      }
    }
  }
  /**
   * Clear all values. Set each value to null.
   * @param dataTypes An array of datatypes. Only values of that datatypes will be cleared.
   * Empty array means all datatypes [default:empty array]
   */
  public function clearValues(array $dataTypes=array())
  {
    $valuesToIgnore = array();
    $mapper = $this->getMapper();
    if ($mapper)
    {
      if (sizeof($dataTypes) > 0)
      {
        $attributesToCopy = $mapper->getAttributes($dataTypes);
        $valuesToCopy = array();
        foreach ($attributesToCopy as $attribute) {
          $valuesToCopy[] = $attribute->name();
        }
        $valuesToIgnore = array_diff($this->getValueNames(), $valuesToCopy);
      }
    }
    $processor = new NodeProcessor('clearValueIntern', array($dataTypes), $this);
    $processor->run($this, false);
  }
  /**
   * Private callback for clearing values
   * @see NodeProcessor
   */
  private function clearValueIntern(Node $node, $valueName, array $dataTypes, array $valuesToIgnore)
  {
    if (!in_array($valueName, $valuesToIgnore)) {
      $node->setValue($valueName, null, $dataType);
    }
  }
  /**
   * Recalculate the object id
   */
  private function updateOID()
  {
    $mapper = $this->getMapper();
    if ($mapper != null)
    {
      $pkValues = array();
      // collect the values of the primary keys and compose the oid from them
      $pkNames = $mapper->getPkNames();
      foreach ($pkNames as $pkName) {
        array_push($pkValues, $this->getValue($pkName));
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
  protected function beforeInsert() {}
  /**
   * This method is called once after inserting the newly created object into the store.
   */
  protected function afterInsert() {}
  /**
   * This method is called always after loading the object from the store.
   */
  public function afterLoad() {}
  /**
   * This method is called always before updating the modified object in the store.
   */
  protected function beforeUpdate() {}
  /**
   * This method is called always after updating the modified object in the store.
   */
  protected function afterUpdate() {}
  /**
   * This method is called once before deleting the object from the store.
   */
  protected function beforeDelete() {}
  /**
   * This method is called once after deleting the object from the store.
   */
  protected function afterDelete() {}

  /**
   * Values and Properties
   */

  /**
   * Check if the node has a given item.
   * @param name The name of the item to query.
   * @return True/False wether the item exists or not.
   */
  public function hasValue($name)
  {
    return in_array($name, $this->getValueNames());
  }
  /**
   * Get the value of a named item.
   * @param name The name of the item to query.
   * @return The value of the item / null if it doesn't exits.
   */
  public function getValue($name)
  {
    if ($this->hasValue($name)) {
      return $this->_data[$name]['value'];
    }
    else {
      return null;
    }
  }
  /**
   * Remove a named item.
   * @param name The name of the item to remove.
   */
  public function removeValue($name)
  {
    if ($this->hasValue($name)) {
      unset($this->_data[$name]);
    }
  }
  /**
   * Get the unconverted value of a named item.
   * @param name The name of the item to query.
   * @return The unconverted value of the item / null if it doesn't exits.
   */
  public function getUnconvertedValue($name)
  {
    $value = $this->getValue($name);
    $dataConverter = $this->getDataConverter();
    if (is_object($dataConverter) && $value != null) {
      $value = $dataConverter->convertApplicationToStorage($value, $this->getValueProperty($name, 'db_data_type'), $name);
    }
    return $value;
  }
  /**
   * Get the converted value of a named item.
   * @param name The name of the item to query.
   * @return The converted value of the item / null if it doesn't exits.
   * @note The result is normally equal to that of PersistentObject::getValue() except, when the value is unconverted
   */
  public function getConvertedValue($name)
  {
    $value = $this->getValue($name);
    $dataConverter = $this->getDataConverter();
    if (is_object($dataConverter) && $value != null) {
      $value = $dataConverter->convertStorageToApplication($value, $this->getValueProperty($name, 'type'), $name);
    }
    return $value;
  }
  /**
   * Check if data may be set. The method is also called, when setting a value.
   * Controller may call this method before setting data and saving the object.
   * Throws a ValidationException in case of invalid data.
   * @param name The name of the item to set.
   * @param value The value of the item.
   * The default implementation calls PersistentObject::validateValueAgainstRestrictions().
   * @note Subclasses will override this method to implement special application requirements.
   */
  public function validateValue($name, $value)
  {
    $this->validateValueAgainstRestrictions($name, $value);
  }
  /**
   * Check a value's value against the restrictions set on it. This method uses the
   * restrictionsMatch and restrictionsNotMatch properties of the attribute definition.
   * Throws a ValidationException if the restrictions don't match.
   * @param name The name of the item to set.
   * @param value The value of the item.
   */
  protected function validateValueAgainstRestrictions($name, $value)
  {
    $restrictionsMatch = $this->getValueProperty($name, 'restrictions_match');
    $restrictionsNotMatch = $this->getValueProperty($name, 'restrictions_not_match');
    if (($restrictionsMatch == '' || preg_match("/".$restrictionsMatch."/m", $value)) &&
        ($restrictionsNotMatch == '' || !preg_match("/".$restrictionsNotMatch."/m", $value))) {
      return;
    }
    // construct the error message
    $errorMessage = Message::get("Wrong value for %1% (%2%). ", array($name, $value));

    // use configured message if existing
    $restrictionsDescription = $this->getValueProperty($name, 'restrictions_description');
    if (strlen($restrictionsDescription) > 0) {
      $errorMessage .= Message::get($restrictionsDescription);
    }
    else
    {
      // construct default message
      if (strlen($restrictionsMatch) > 0) {
        $errorMessage .= Message::get("The value must match %1%.", array($restrictionsMatch));
      }
      if (strlen($restrictionsNotMatch) > 0) {
        $errorMessage .= Message::get("The value must NOT match %1%.", array($restrictionsNotMatch));
      }
    }
    throw new ValidationException($errorMessage);
  }
  /**
   * Set the value of a named item if it exists.
   * @param name The name of the item to set.
   * @param value The value of the item.
   * @param forceSet Set the value even if it is already set and validation would fail (used to notify listeners) [default: false]
   * @return true if operation succeeds / false else
   */
  public function setValue($name, $value, $forceSet=false)
  {
    if (!array_key_exists($name, $this->_data)) {
      $this->_data[$name] = array('value' => null);
    }
    if (!$forceSet)
    {
      try {
        $this->validateValue($name, $value);
      }
      catch(ValidationException $ex) {
        $msg = "Invalid value (".$value.") for ".$this->getOID().".".$name.": ".$ex->getMessage();
        throw new ValidationException($msg);
      }
    }
    $oldValue = $this->getValue($name);
    if ($this->_data[$name]['value'] !== $value || $forceSet)
    {
      $this->_data[$name]['value'] = $value;
      PersistentObject::setState(STATE_DIRTY);
      $mapper = $this->getMapper();
      if ($mapper != null && in_array($name, $mapper->getPKNames())) {
        $this->updateOID();
      }
      $this->propagateValueChange($name, $oldValue, $value);
      return true;
    }
    return false;
  }
  /**
   * Get the value of one property of a named item.
   * @param name The name of the item to get its properties.
   * @param property The name of the property to set.
   * @return The value property/null if not found.
   */
  public function getValueProperty($name, $property)
  {
    if (!isset($this->_data[$name]['properties']) || !isset($this->_data[$name]['properties'][$property])) {
      // get the default property value from the mapper
      $mapper = $this->getMapper();
      if ($mapper)
      {
        if ($mapper->hasAttribute($name))
        {
          $attr = $mapper->getAttribute($name);
          if ($attr) {
            return $attr->$property;
          }
        }
      }
    }
    else
    {
      // the default property value is overidden
      return $this->_data[$name]['properties'][$property];
    }
    return false;
  }
  /**
   * Set the value of one property of a named item.
   * @param name The name of the item to set its properties.
   * @param property The name of the property to set.
   * @param value The value to set on the property.
   */
  public function setValueProperty($name, $property, $value)
  {
    if (!array_key_exists('properties', $this->_data[$name])) {
      $this->_data[$name]['properties'] = array();
    }
    $this->_data[$name]['properties'][$property] = $value;
  }
  /**
   * Get the names of all properties of a value in the object.
   * @return An array consisting of the names.
   */
  public function getValuePropertyNames($name)
  {
    $result = array();
    $mapper = $this->getMapper();
    if ($mapper)
    {
      if ($mapper->hasAttribute($name))
      {
        $attr = $mapper->getAttribute($name);
        if ($attr) {
          $result = $attr->getPropertyNames();
        }
      }
    }
    return array_merge($result, array_keys($this->_data[$name]['properties']));
  }
  /**
   * Get the names of all items.
   * @return An array of all item names.
   */
  public function getValueNames()
  {
    $names = array_keys($this->_data);
    return $names;
  }
  /**
   * Get the value of a named property in the object.
   * @param name The name of the property to query.
   * @return The value of the property / null if it doesn't exits.
   */
  public function getProperty($name)
  {
    if (array_key_exists($name, $this->_properties)) {
      return $this->_properties[$name];
    }
    else {
      // get the default property value from the mapper
      $mapper = $this->getMapper();
      if ($mapper)
      {
        $properties = $mapper->getProperties();
        if (array_key_exists($name, $properties)) {
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
  public function setProperty($name, $value)
  {
    $oldValue = $this->getProperty($name);
    $this->_properties[$name] = $value;
    $this->propagatePropertyChange($name, $oldValue, $value);
  }
  /**
   * Get the names of all properties in the object.
   * @return An array consisting of the names.
   */
  public function getPropertyNames()
  {
    $result = array();
    $mapper = $this->getMapper();
    if ($mapper)
    {
      $result = array_keys($mapper->getProperties());
    }
    return array_merge($result, array_keys($this->_properties));
  }

  /**
   * ChangeListener Support
   */

  /**
   * Add a change listener (Must be of type ChangeListener).
   * @param listener The ChangeListener.
   */
  public function addChangeListener(ChangeListener $listener)
  {
    $this->_changeListeners[sizeof($this->_changeListeners)] = &$listener;
  }
  /**
   * Remove a change listener (Must be of type ChangeListener).
   * @param listener The ChangeListener.
   */
  public function removeChangeListener(ChangeListener $listener)
  {
    for ($i=0; $i<sizeof($this->_changeListeners); $i++)
    {
      if ($this->_changeListeners[$i]->getId() == $listener->getId()) {
        unset($this->_changeListeners[$i]);
      }
    }
  }
  /**
   * Notify ChangeListeners of value changes.
   * @param name The name of the item that has changed.
   * @param oldValue The old value of the item that has changed
   * @param newValue The new value of the item that has changed
   */
  private function propagateValueChange($name, $oldValue, $newValue)
  {
    for ($i=0; $i<sizeof($this->_changeListeners); $i++) {
      if(method_exists($this->_changeListeners[$i], 'valueChanged')) {
        $this->_changeListeners[$i]->valueChanged($this, $name, $oldValue, $newValue);
      }
    }
  }
  /**
   * Notify ChangeListeners of property changes.
   * @param name The name of the item that has changed.
   * @param oldValue The old value of the item that has changed
   * @param newValue The new value of the item that has changed
   */
  private function propagatePropertyChange($name, $oldValue, $newValue)
  {
    for ($i=0; $i<sizeof($this->_changeListeners); $i++) {
      if(method_exists($this->_changeListeners[$i], 'propertyChanged')) {
        $this->_changeListeners[$i]->propertyChanged($this, $name, $oldValue, $newValue);
      }
    }
  }
  /**
   * Notify ChangeListeners of state changes.
   * @param oldValue The old value of the item that has changed
   * @param newValue The new value of the item that has changed
   */
  private function propagateStateChange($oldValue, $newValue)
  {
    for ($i=0; $i<sizeof($this->_changeListeners); $i++) {
      if(method_exists($this->_changeListeners[$i], 'stateChanged')) {
        $this->_changeListeners[$i]->stateChanged($this, $oldValue, $newValue);
      }
    }
  }

  /**
   * Output
   */

  /**
   * Get the name of the type used for display.
   * @return The name.
   * @note Sublasses will override this for special application requirements
   */
  public function getObjectDisplayName()
  {
    return Message::get($this->getType());
  }
  /**
   * Get the description of the type.
   * @return The description.
   * @note Sublasses will override this for special application requirements
   */
  public function getObjectDescription()
  {
    return Message::get($this->getType());
  }
  /**
   * Get the value of the object used for display.
   * @return The value.
   * @note Sublasses will override this for special application requirements
   */
  public function getDisplayValue()
  {
    return $this->__toString();
  }
  /**
   * Get the name of a value used for display.
   * @param name The name of the value.
   * @param type The type of the value (not used by the default implementation) [default: null]
   * @return The name of the value.
   * @note Sublasses will override this for special application requirements
   */
  public function getValueDisplayName($name)
  {
    return Message::get($name);
  }
  /**
   * Get the description of a value.
   * @param name The name of the value.
   * @return The description of the value.
   * @note Sublasses will override this for special application requirements
   */
  public function getValueDescription($name)
  {
    return Message::get($name);
  }
  /**
   * TODO: __toString returns only display values + oid, verbose dump maybe obtained by var_dump
   * Get a string representation of the PersistentObject.
   * @param verbose True to get a verbose output [default: false]
   * @return The string representation of the PersistentObject.
   */
  public function __toString($verbose=false)
  {
    $str = 'type:'.$this->getType().', ';
    $mapper = $this->getMapper();
    if ($mapper != null) {
      $str .= 'mapper:'.get_class($mapper).', ';
    }
    $str .= 'oid:'.$this->getOID().' ';
    $str .= 'state:'.$this->getState().' ';
    $str .= 'PROPERTIES ';
    foreach($this->getPropertyNames() as $name)
    {
      $value = $this->getProperty($name);
      if (is_array($value)) {
        $str .= $name.':'.JSONUtil::encode($value).' ';
      }
      else {
        $str .= $name.':'.$value.' ';
      }
    }
    $str .= "\n";
    $str .= 'VALUES [';
    $valueNames = $this->getValueNames();
    foreach($valueNames as $name)
    {
      $str .= $name.':'.$this->getValue($name).' ';
      if ($verbose)
      {
        $valueProperties = $this->_data[$name]['properties'];
        if (sizeOf($valueProperties) > 0)
        {
          $str .= '[';
          foreach($valueProperties as $key => $value) {
            $str .= $key.':'.$value.' ';
          }
          $str = substr($str, 0, strlen($str)-1);
          $str .= '] ';
        }
      }
    }
    $str = substr($str, 0, -1);
    $str .= "\n";
    return $str;
  }

  /**
   * Check if the instance object is contained in the search index
   * @return True/False wether the object is contained or not
   */
  protected function isIndexInSearch()
  {
    return (boolean) $this->getProperty('is_searchable');
  }

  /**
   * Add the instance to the search index
   */
  public function indexInSearch()
  {
    if ($this->isIndexInSearch())
    {
      $index = SearchUtil::getIndex();
      $encoding = new EncodingUtil();

      $doc = new Zend_Search_Lucene_Document();

      $valueNames = $this->getValueNames();

      $doc->addField(Zend_Search_Lucene_Field::unIndexed('oid', $this->getOID(), 'utf-8'));
      $typeField = Zend_Search_Lucene_Field::keyword('type', $this->getType(), 'utf-8');
      $typeField->isStored = false;
      $doc->addField($typeField);

      foreach ($valueNames as $currValueName)
      {
        list($valueType) = $this->getValueTypes($currValueName);

        if ($valueType == DATATYPE_ATTRIBUTE)
        {
          $value = $this->getValue($currValueName);

          switch($this->getValueProperty($currValueName, 'input_type'))
          {
            case 'text':
              $doc->addField(Zend_Search_Lucene_Field::unStored($currValueName, $encoding->convertIsoToCp1252Utf8($value), 'utf-8'));
              break;

            case 'fckeditor':
              $doc->addField(Zend_Search_Lucene_Field::unStored($currValueName,
                html_entity_decode($encoding->convertIsoToCp1252Utf8(strip_tags($value)), ENT_QUOTES,'utf-8'), 'utf-8'));
              break;

            default:
              $field = Zend_Search_Lucene_Field::keyword($currValueName, $value, 'utf-8');
              $field->isStored = false;
              $doc->addField($field);
          }
        }
      }

      $term = new Zend_Search_Lucene_Index_Term($this->getOID(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id)
      {
        $index->delete($id);
      }

      $index->addDocument($doc);
    }
  }

  /**
   * Delete this instance from the search index.
   */
  protected function deleteFromSearchIndex()
  {
    if ($this->isIndexInSearch())
    {
      $index = SearchUtil::getIndex();

      $term = new Zend_Search_Lucene_Index_Term($this->getOID(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id)
      {
        $index->delete($id);
      }
    }
  }
}
?>