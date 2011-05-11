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
require_once(WCMF_BASE."wcmf/lib/util/class.Message.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.IPersistenceMapper.php");
require_once(WCMF_BASE."wcmf/lib/persistence/locking/class.LockManager.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceException.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ValidationException.php");
require_once(WCMF_BASE."wcmf/lib/util/class.SearchUtil.php");

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
  const STATE_CLEAN = 0;
  const STATE_DIRTY = 1;
  const STATE_NEW = 2;
  const STATE_DELETED = 3;

  private $_oid = null;                // object identifier
  private $_type = '';                 // the object type
  private $_data = array();            // associative array holding the data
  private $_properties = array();      // associative array holding the properties
  private $_state = self::STATE_CLEAN;       // the state of the PersistentObject
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
      $this->setState(self::STATE_NEW, false);
    }
    else
    {
      // old node
      $this->setOID($oid);
      $this->setState(self::STATE_CLEAN, false);
    }
  }
  /**
   * Initialize the object with a set of data. This method does not change the
   * object's state and does not call any listeners. Any existing data will
   * be overwritten.
   * @param data An associative array with the data to set.
   */
  public function initialize(array $data)
  {
    foreach ($data as $name => $value) {
      $this->_data[$name] = array('value' => $value);
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
   * @return ObjectId
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
   * @return IPersistenceMapper
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
   * @return DataConverter
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
   * Save data. This call will be delegated to the PersistenceFacade class.
   */
  public function save()
  {
    if (!$this->_isImmutable)
    {
      $oldState = $this->getState();
      // call before hook method
      if ($oldState == self::STATE_NEW) {
        $this->beforeInsert();
      }
      elseif ($oldState == self::STATE_DIRTY) {
        $this->beforeUpdate();
      }
      // save the object
      $persistenceFacade = PersistenceFacade::getInstance();
      $persistenceFacade->save($this);

      // update search index
      SearchUtil::indexInSearch($this);

      // call after hook method
      if ($oldState == self::STATE_NEW) {
        $this->afterInsert();
      }
      elseif ($oldState == self::STATE_DIRTY) {
        $this->afterUpdate();
      }
    }
    else {
      throw new PersistenceException(Message::get("Cannot save immutable object '%1%'.", array($this->getOID())));
    }
  }
  /**
   * Delete data. This call will be delegated to the PersistenceFacade class.
   */
  public function delete()
  {
    if (!$this->_isImmutable)
    {
      // call before hook method
      $this->beforeDelete();

      // delete the object
      $persistenceFacade = PersistenceFacade::getInstance();
      $persistenceFacade->delete($this->getOID());

      // remove from index
      SearchUtil::deleteFromSearch($this);

      // call after hook method
      $this->afterDelete();
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
      case self::STATE_NEW:
        switch ($state)
        {
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
   * @return Lock instance as provided by LockManager::getLock() or null if not locked
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
   * @return PersistentObject
   */
  public function duplicate()
  {
    $class = get_class($this);
    $copy = new $class;
    $copy->_oid = $this->_oid;
    $copy->_type = $this->_type;
    $copy->_requestData = $this->_data;
    $copy->_properties = $this->_properties;
    $copy->_state = $this->_state;
    $copy->_isImmutable = $this->_isImmutable;

    return $copy;
  }
  /**
   * Copy all non-empty values to a given instance (ChangeListeners are triggered)
   * @param object A reference to the PersistentObject to copy the values to.
   * @param copyPkValues True/False wether primary key values should be copied
   */
  public function copyValues(PersistentObject $object, $copyPkValues=true)
  {
    $valuesToIgnore = array();
    $mapper = $this->getMapper();
    if ($mapper) {
      if (!$copyPkValues) {
        $valuesToIgnore = $mapper->getPkNames();
      }
    }
    $iter = new NodeValueIterator($this, false);
    while(!$iter->isEnd())
    {
      $curNode = $iter->getCurrentNode();
      $valueName = $iter->getCurrentAttribute();
      if (!isset($valuesToIgnore[$valueName]))
      {
        $value = $curNode->getValue($valueName);
        if (strlen($value) > 0) {
          $object->setValue($valueName, $value, true);
        }
      }
      $iter->proceed();
    }
  }
  /**
   * Clear all values. Set each value to null.
   */
  public function clearValues()
  {
    $iter = new NodeValueIterator($this, false);
    while(!$iter->isEnd())
    {
      $curNode = $iter->getCurrentNode();
      $curNode->setValue($iter->getCurrentAttribute(), null);
      $iter->proceed();
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
    if (isset($this->_data[$name])) {
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
   * Validate all values
   * @return Empty string if validation succeeded, an error string else. The default implementation returns the result of
   *        PersistentObject::validateValueAgainstRestrictions().
   */
  public function validateValues()
  {
    $errorMsg = '';
    $iter = new NodeValueIterator($this, false);
    while(!$iter->isEnd())
    {
      $curNode = $iter->getCurrentNode();
      $valueName = $iter->getCurrentAttribute();
      $error = $curNode->validateValue($valueName, $value);
      if (strlen($error) > 0) {
        $errorMsg .= $error."\n";
      }
      $iter->proceed();
    }
    return $errorMsg;
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
   * @param forceSet True/False wether to set the value even if it is already set
   *   and validation would fail (used to notify listeners) [default: false]
   * @return true if operation succeeds / false else
   */
  public function setValue($name, $value, $forceSet=false)
  {
    if (!isset($this->_data[$name])) {
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
    if ($oldValue !== $value || $forceSet)
    {
      $this->setValueInternal($name, $value);
      self::setState(self::STATE_DIRTY);
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
   * Internal (fast) version to set a value without any validation, state change,
   * listener notification etc.
   * @param name The name of the value
   * @param value The value
   */
  protected function setValueInternal($name, $value)
  {
    $this->_data[$name]['value'] = $value;
  }
  /**
   * Add a value to an array value. If the value is not set yet, an array will
   * be created. If the value is set already and it is no array a warning will be
   * logged without setting the value.
   * @param name The name of the item to add the value to.
   * @param value The value to add.
   * @param forceSet Add the value even if it is already set and validation would fail (used to notify listeners) [default: false]
   * @return true if operation succeeds / false else
   */
  public function addValue($name, $value, $forceSet=false)
  {
    $existingValue = $this->getValue($name);
    $isEmpty = empty($existingValue);
    if (!$isEmpty && !is_array($existingValue)) {
      Log::warn("Can't add to the non-array value '".$name."'", __CLASS__);
    }
    else {
      if ($isEmpty) {
        $newValue = array($value);
      }
      else {
        $existingValue[] = $value;
        $newValue = $existingValue;
      }
      return $this->setValue($name, $newValue, $forceSet);
    }
    return false;
  }
  /**
   * Get the value of one property of a named item.
   * @param name The name of the item to get its properties.
   * @param property The name of the property to get.
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
            $getter = "get".ucfirst(StringUtil::underScoreToCamelCase($property, true));
            return $attr->$getter();
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
    if (!isset($this->_data[$name]['properties'])) {
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
   * Get the names of all persistent items.
   * @return An array of item names.
   */
  public function getPersistentValueNames()
  {
    $names = array();
    $mapper = $this->getMapper();
    if ($mapper)
    {
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
    if (isset($this->_properties[$name])) {
      return $this->_properties[$name];
    }
    else {
      // get the default property value from the mapper
      $mapper = $this->getMapper();
      if ($mapper)
      {
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
  public function setProperty($name, $value)
  {
    $oldValue = $this->getProperty($name);
    $this->_properties[$name] = $value;
    $this->propagatePropertyChange($name, $oldValue, $value);
  }
  /**
   * Get the names of all properties in the object. Properties are
   * either defined by using the PersistentObject::setProperty() method
   * or by the PersistentMapper.
   * @param excludeDefaultProperties True/False wether to only return the
   *   properties defined by the PersistentObject::setProperty() method or
   *   also the properties defined by the mapper [default: false]
   * @return An array consisting of the names.
   */
  public function getPropertyNames($excludeDefaultProperties=false)
  {
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
   * ChangeListener Support
   */

  /**
   * Add a change listener.
   * @param listener The ChangeListener instance.
   */
  public function addChangeListener(IChangeListener $listener)
  {
    $this->_changeListeners[sizeof($this->_changeListeners)] = &$listener;
  }
  /**
   * Remove a change listener.
   * @param listener The ChangeListener instance.
   */
  public function removeChangeListener(IChangeListener $listener)
  {
    for ($i=0, $count=sizeof($this->_changeListeners); $i<$count; $i++) {
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
    for ($i=0, $count=sizeof($this->_changeListeners); $i<$count; $i++) {
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
    for ($i=0, $count=sizeof($this->_changeListeners); $i<$count; $i++) {
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
    for ($i=0, $count=sizeof($this->_changeListeners); $i<$count; $i++) {
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
   * Get the display value names of the object.
   * (defined by the property 'is_searchable')
   * @return An array of value names
   */
  public function getDisplayValueNames()
  {
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
   * Get a string representation of the PersistentObject.
   * @param verbose True to get a verbose output [default: false]
   * @return The string representation of the PersistentObject.
   */
  public function __toString()
  {
    return $this->getOID()->__toString();
  }

  /**
   * Check if the instance object is contained in the search index
   * (defined by the property 'is_searchable')
   * @return True/False wether the object is contained or not
   */
  protected function isIndexInSearch()
  {
    return (boolean) $this->getProperty('is_searchable');
  }
}
?>