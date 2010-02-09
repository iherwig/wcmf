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
require_once(BASE."wcmf/lib/presentation/format/class.IFormat.php");

/**
 * @class AbstractFormat
 * @ingroup Format
 * @brief AbstractFormat maybe used as base class for specialized formats.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractFormat implements IFormat
{
  var $_deserializedNodes = null;

  /**
   * Get a node with the given oid to deserialize values from form fields into it.
   * The method ensures that there is only one instance per oid.
   * @param oid The oid
   * @return A reference to the Node instance
   */
  function getNode($oid)
  {
    if (!is_array($this->_deserializedNodes)) {
      $this->_deserializedNodes = array();
    }
    if (!array_key_exists($oid, $this->_deserializedNodes))
    {
      $persistenceFacade = &PersistenceFacade::getInstance();
      $type = PersistenceFacade::getOIDParameter($oid, 'type');
      // don't create all values by default (-> don't use PersistenceFacade::create())
      $node = new Node($type);
      $node->setOID($oid);
      $this->_deserializedNodes[$oid] = &$node;
    }
    return $this->_deserializedNodes[$oid];
  }

  /**
   * Get all serialized nodes
   * @return An array of Node references
   */
  function getNodes()
  {
    return $this->_deserializedNodes;
  }
}
?>
