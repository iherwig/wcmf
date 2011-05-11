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
 * @interface IChangeListener
 * @ingroup Persistence
 * @brief IChangeListener defines an interface for classes that want to
 * be notified when a value of an persistent object changes.

 * @author ingo herwig <ingo@wemove.com>
 */
interface IChangeListener
{
  /**
   * Get a unique id for the listener.
   * @return The id
   */
  function getId();

  /**
   * This method is called, when a named item has changed.
   * @param object A reference to the PersistentObject that holds the item
   * @param name The name of the item that has changed.
   * @param oldValue The old value of the item that has changed
   * @param newValue The new value of the item that has changed
   */
  function valueChanged(PersistentObject $object, $name, $oldValue, $newValue);

  /**
   * This method is called, when a property has changed.
   * @param object A reference to the PersistentObject that holds the property
   * @param name The name of the property that has changed.
   * @param oldValue The old value of the item that has changed
   * @param newValue The new value of the item that has changed
   */
  function propertyChanged(PersistentObject $object, $name, $oldValue, $newValue);

  /**
   * This method is called, when the state has changed.
   * @param object A reference to the PersistentObject that changed it's state
   * @param oldValue The old value of the state
   * @param newValue The new value of the state
   */
  function stateChanged(PersistentObject $object, $oldValue, $newValue);
}
?>