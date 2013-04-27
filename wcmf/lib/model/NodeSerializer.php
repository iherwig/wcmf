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
namespace wcmf\lib\model;

use wcmf\lib\model\Node;

/**
 * NodeSerializer implementations are used to serialize Nodes into an
 * array representation or deserialize an array representation into Nodes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface NodeSerializer {

  /**
   * Check if the given data represent a serialized Node
   * @param data A variable of any type
   * @return boolean
   */
  public function isSerializedNode($data);

  /**
   * Deserialize a Node from serialized data. Only values given in data are being set.
   * @param data An array containing the serialized Node data
   * @param parent The parent Node [default: null]
   * @param role The role of the serialized Node in relation to parent [default: null]
   * @return An array with keys 'node' and 'data' where the node
   * value is the Node instance and the data value is the
   * remaining part of data, that is not used for deserializing the Node
   */
  public function deserializeNode($data, Node $parent=null, $role=null);

  /**
   * Serialize a Node into an array
   * @param node A reference to the node to serialize
   * @return The node serialized into an associated array
   */
  public function serializeNode(Node $node);
}
?>
