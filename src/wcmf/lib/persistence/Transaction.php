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
   * Set the transaction active (object changes will be recorded).
   */
  public function begin();

  /**
   * Commit the object changes to the storage.
   * Sets the transaction to inactive.
   * @return Array of executed statements (see PersistenceMapper::getStatements())
   */
  public function commit();

  /**
   * Collect object change statements. Acts like a normal commit, but does not execute the statements.
   * Sets the transaction to inactive.
   * @return Array of statements to be executed (see PersistenceMapper::getStatements())
   */
  public function commitCollect();

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
   * Attach an object to the transaction. The returned object is the attached instance.
   * @param $object The object
   * @return PersistentObject
   */
  public function attach(PersistentObject $object);

  /**
   * Detach an object from the transaction. All local changes will not
   * be stored. Afterwards the object is unknown to the transaction.
   * @param $oid The object id of the object
   */
  public function detach(ObjectId $oid);

  /**
   * Get a loaded object.
   * @param $oid ObjectId of the object
   * @return PersistentObject instance or null if not loaded yet
   */
  public function getLoaded(ObjectId $oid);

  /**
   * Get all objects currently involved in the transaction
   * @return Array of PersistentObject instances
   */
  public function getObjects();
}
?>