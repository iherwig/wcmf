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
namespace wcmf\lib\persistence\output\impl;

use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\output\OutputStrategy;

/**
 * ArrayOutputStrategy outputs an object's content into an array.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ArrayOutputStrategy implements OutputStrategy {

  private $writeValueProperties = false;

  /**
   * Constructor
   * @param $writeValueProperties Boolean whether to write value properties or not (default: _false_)
   */
  public function __construct($writeValueProperties=false) {
    $this->writeValueProperties = $writeValueProperties;
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    // do nothing
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    // do nothing
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    $content = array();
    $content['oid'] = $obj->getOID();
    $content['type'] = $obj->getType();
    $content['properties'] = array();
    foreach($obj->getPropertyNames() as $name) {
      $content['properties'][$name] = $this->writeValue($obj->getProperty($name));
    }
    $content['values'] = array();
    foreach($obj->getValueNames() as $name) {
      $content['values'][$name] = array();
      $value = $this->writeValue($obj->getValue($name));
      $content['values'][$name]['value'] = $value;
      if ($this->writeValueProperties) {
        $content['values'][$name]['properties'] = array();
        foreach($obj->getValuePropertyNames($name) as $propertyName) {
          $content['values'][$name]['properties'][$propertyName] = $this->writeValue($obj->getValueProperty($name, $propertyname));
        }
      }
    }
    return $content;
  }

  /**
   * Write the objects content.
   * @param $value The value to write.
   */
  private function writeValue($value) {
    $content = '';
    if (is_array($value)) {
      $content = array();
      for ($i=0; $i<sizeof($value); $i++) {
        $content[] = utf8_encode($value[$i]);
      }
    }
    else if ($value instanceof PersistentObject) {
      $content = $this->writeObject($value);
    }
    else {
      $content = utf8_encode($value);
    }
    return $content;
  }
}
?>
