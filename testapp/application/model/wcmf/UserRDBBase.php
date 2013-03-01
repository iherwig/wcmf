<?php
/*
 * Copyright (c) 2013 The Olympos Development Team.
 * 
 * http://sourceforge.net/projects/olympos/
 * 
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
 * this entire header must remain intact.
 */

/**
 * This file was generated by ChronosGenerator  from cwm-export.uml on Fri Mar 01 16:46:25 CET 2013. 
 * Manual modifications should be placed inside the protected regions.
 */
namespace testapp\application\model\wcmf;

use wcmf\lib\security\principal\impl\AbstractUser;

use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\ObjectId;

/**
 * @class UserRDB
 * UserRDB description: ?
 *
 * @author 
 * @version 1.0
 */
class UserRDBBase extends AbstractUser {

    /**
     * Constructor
     * @param oid ObjectId instance (optional)
     */
    public function __construct($oid=null) {
      if ($oid == null) {
        $oid = new ObjectId('UserRDB');
    }
      parent::__construct($oid);
    }

    /**
     * @see PersistentObject::getObjectDisplayName()
     */
    public function getObjectDisplayName() {
      return Message::get("UserRDB");
    }

    /**
     * @see PersistentObject::getObjectDescription()
     */
    public function getObjectDescription() {
      return Message::get("?");
    }

    /**
     * @see PersistentObject::getValueDisplayName()
     */
    public function getValueDisplayName($name) {
      $displayName = $name;
      if ($name == 'id') { $displayName = Message::get("id"); }
      if ($name == 'login') { $displayName = Message::get("login"); }
      if ($name == 'password') { $displayName = Message::get("password"); }
      if ($name == 'name') { $displayName = Message::get("name"); }
      if ($name == 'firstname') { $displayName = Message::get("firstname"); }
      if ($name == 'config') { $displayName = Message::get("config"); }
      return Message::get($displayName);
    }

    /**
     * @see PersistentObject::getValueDescription()
     */
    public function getValueDescription($name) {
      $description = $name;
      if ($name == 'id') { $description = Message::get(""); }
      if ($name == 'login') { $description = Message::get("?"); }
      if ($name == 'password') { $description = Message::get("?"); }
      if ($name == 'name') { $description = Message::get("?"); }
      if ($name == 'firstname') { $description = Message::get("?"); }
      if ($name == 'config') { $description = Message::get("?"); }
      return Message::get($description);
    }

    /**
     * Get the value of the id attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getId($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('id'); }
      else { $value = $this->getValue('id'); }
      return $value;
    }

    /**
     * Set the value of the id attribute
     * @param id The value to set
     */
    public function setId($id) {
      return $this->setValue('id', $id);
    }
    /**
     * Get the value of the login attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getLogin($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('login'); }
      else { $value = $this->getValue('login'); }
      return $value;
    }

    /**
     * Set the value of the login attribute
     * @param login The value to set
     */
    public function setLogin($login) {
      return $this->setValue('login', $login);
    }
    /**
     * Get the value of the password attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getPassword($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('password'); }
      else { $value = $this->getValue('password'); }
      return $value;
    }

    /**
     * Set the value of the password attribute
     * @param password The value to set
     */
    public function setPassword($password) {
      return $this->setValue('password', $password);
    }
    /**
     * Get the value of the name attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getName($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('name'); }
      else { $value = $this->getValue('name'); }
      return $value;
    }

    /**
     * Set the value of the name attribute
     * @param name The value to set
     */
    public function setName($name) {
      return $this->setValue('name', $name);
    }
    /**
     * Get the value of the firstname attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getFirstname($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('firstname'); }
      else { $value = $this->getValue('firstname'); }
      return $value;
    }

    /**
     * Set the value of the firstname attribute
     * @param firstname The value to set
     */
    public function setFirstname($firstname) {
      return $this->setValue('firstname', $firstname);
    }
    /**
     * Get the value of the config attribute
     * @param unconverted Boolean wether to get the converted or stored value (default: false)
     * @return Mixed
     */
    public function getConfig($unconverted=false) {
      $value = null;
      if ($unconverted) { $value = $this->getUnconvertedValue('config'); }
      else { $value = $this->getValue('config'); }
      return $value;
    }

    /**
     * Set the value of the config attribute
     * @param config The value to set
     */
    public function setConfig($config) {
      return $this->setValue('config', $config);
    }
     
    /**
     * Get the Locktable instances in the Locktable relation
     * @return Array of Locktable instances
     */
    public function getLocktableList() {
      return $this->getChildrenEx(null, 'Locktable', null, null, null, false);
    }

    /**
     * Set the Locktable instances in the Locktable relation
     * @param nodeList Array of Locktable instances
     */
    public function setLocktableList(array $nodeList) {
      $this->setValue('Locktable', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'Locktable');
        }
      }
    /**
     * Get the UserConfig instances in the UserConfig relation
     * @return Array of UserConfig instances
     */
    public function getUserConfigList() {
      return $this->getChildrenEx(null, 'UserConfig', null, null, null, false);
    }

    /**
     * Set the UserConfig instances in the UserConfig relation
     * @param nodeList Array of UserConfig instances
     */
    public function setUserConfigList(array $nodeList) {
      $this->setValue('UserConfig', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'UserConfig');
        }
      }
    /**
     * Get the RoleRDB instances in the RoleRDB relation
     * @return Array of RoleRDB instances
     */
    public function getRoleRDBList() {
      return $this->getChildrenEx(null, 'RoleRDB', null, null, null, false);
    }

    /**
     * Set the RoleRDB instances in the RoleRDB relation
     * @param nodeList Array of RoleRDB instances
     */
    public function setRoleRDBList(array $nodeList) {
      $this->setValue('RoleRDB', null);
      foreach ($nodeList as $node) {
        $this->addNode($node, 'RoleRDB');
        }
      }
}
?>
