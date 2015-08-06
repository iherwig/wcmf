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

/**
 * Transaction implements the Unit of Work pattern as it defines
 * the interface for maintaining a list of PersistentObject changes inside
 * a business transaction and commit/rollback them.
 * Transaction also serves as an Identity Map for loaded objects.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Transaction {

  /**
   * Register a loaded object. Mappers must call this method on each
   * loaded object. The returned object is the registered instance.
   * @param $object PersistentObject instance
   * @return PersistentObject instance
   */
  public function registerLoaded(PersistentObject $object);

  /**
   * Register a newly created object
   * @param $object PersistentObject instance
   */
  public function registerNew(PersistentObject $object);

  /**
   * Register a dirty object.
   * @param $object PersistentObject instance
   */
  public function registerDirty(PersistentObject $object);

  /**
   * Register a deleted object.
   * @param $object PersistentObject instance
   */
  public function registerDeleted(PersistentObject $object);

  /**
   * Set the transaction active (object changes will be recorded).
   */
  public function begin();

  /**
   * Commit the object changes to the storage.
   * Sets the transaction to inactive.
   * @return Map of oid changes (key: oid string before commit, value: oid string after commit)
   */
  public function commit();

  /**
   * Discard the object changes.
   * Sets the transaction to inactive.
   */
  public function rollback();

  /**
   * Check if the transaction is active.
   * @return Boolean
   */
  public function isActive();

  /**
   * Get a loaded object.
   * @param $oid ObjectId of the object
   * @return PersistentObject instance or null if not loaded yet
   */
  public function getLoaded(ObjectId $oid);

  /**
   * Detach an object from the transaction. All local changes will not
   * be stored. Afterwards the object is unknown to the transaction.
   * @param $oid The object id of the object
   */
  public function detach(ObjectId $oid);

  /**
   * Get all objects currently involved in the transaction
   * @return Array of PersistentObject instances
   */
  public function getObjects();
}
?>