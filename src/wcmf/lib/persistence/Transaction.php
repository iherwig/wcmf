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
  public function begin(): void;

  /**
   * Commit the object changes to the storage.
   * Sets the transaction to inactive.
   * @return array<mixed> of executed statements (see PersistenceMapper::getStatements())
   */
  public function commit(): array;

  /**
   * Collect object change statements. Acts like a normal commit, but does not execute the statements.
   * Sets the transaction to inactive.
   * @return array<mixed> of statements to be executed (see PersistenceMapper::getStatements())
   */
  public function commitCollect(): array;

  /**
   * Discard the object changes.
   * Sets the transaction to inactive.
   */
  public function rollback(): void;

  /**
   * Check if the transaction is active.
   * @return bool
   */
  public function isActive(): bool;

  /**
   * Attach an object to the transaction. The returned object is the attached instance.
   * @param PersistentObject $object The object
   * @return PersistentObject
   */
  public function attach(PersistentObject $object): PersistentObject;

  /**
   * Detach an object from the transaction. All local changes will not
   * be stored. Afterwards the object is unknown to the transaction.
   * @param ObjectId $oid The object id of the object
   */
  public function detach(ObjectId $oid): void;

  /**
   * Get a loaded object.
   * @param ObjectId $oid ObjectId of the object
   * @return PersistentObject instance or null if not loaded yet
   */
  public function getLoaded(ObjectId $oid): ?PersistentObject;

  /**
   * Get all objects currently involved in the transaction
   * @return array<PersistentObject>
   */
  public function getObjects(): array;
}
?>