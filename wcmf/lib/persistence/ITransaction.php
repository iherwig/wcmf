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

/**
 * @interface ITransaction
 * @ingroup Persistence
 * @brief ITransaction implements the Unit of Work pattern as it defines
 * the interface for maintaining a list of PersistentObject changes inside
 * a business transaction and commit/rollback them.
 * ITransaction also serves as an Identity Map for loaded objects.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ITransaction
{
  /**
   * Register a newly created object
   * @param object PersistentObject instance
   */
  function registerNew(PersistentObject $object);
  /**
   * Register a dirty object.
   * @param object PersistentObject instance
   */
  function registerDirty(PersistentObject $object);
  /**
   * Register a deleted object.
   * @param object PersistentObject instance
   */
  function registerDeleted(PersistentObject $object);
  /**
   * Set the transaction active (object changes will be recorded).
   */
  function begin();
  /**
   * Commit the object changes to the storage.
   * Sets the transaction to inactive.
   */
  function commit();
  /**
   * Discard the object changes.
   * Sets the transaction to inactive.
   */
  function rollback();
  /**
   * Check if the transaction is active.
   * @return Boolean
   */
  function isActive();
  /**
   * Register a loaded object. Mappers must call this method on each
   * loaded object. The returned object is the registered instance.
   * @param object PersistentObject instance
   * @return PersistentObject instance
   */
  function registerLoaded(PersistentObject $object);
  /**
   * Get a loaded object.
   * @param oid ObjectId of the object
   * @param buildAttribs An array listing the attributes to load (default: null, loads all attributes)
   * @return PersistentObject instance or null if not loaded yet
   */
  function getLoaded(ObjectId $oid, $buildAttribs=null);
  /**
   * Detach an object from the transaction. All local changes will not
   * be stored. Afterwards the object is unknown to the transaction.
   * @param object PersistentObject instance
   */
  function detach(PersistentObject $object);
}
?>