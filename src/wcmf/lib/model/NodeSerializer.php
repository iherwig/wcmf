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
   * @param $data A variable of any type
   * @return Boolean
   */
  public function isSerializedNode($data);

  /**
   * Deserialize a Node from serialized data. Only values given in data are being set.
   * @param $data An array containing the serialized Node data
   * @param $parent The parent Node (default: _null_)
   * @param $role The role of the serialized Node in relation to parent (default: _null_)
   * @return An array with keys 'node' and 'data' where the node
   * value is the Node instance and the data value is the
   * remaining part of data, that is not used for deserializing the Node
   */
  public function deserializeNode($data, Node $parent=null, $role=null);

  /**
   * Serialize a Node into an array
   * @param $node A reference to the node to serialize
   * @return The node serialized into an associated array
   */
  public function serializeNode($node);
}
?>
