<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model;

use wcmf\lib\model\Node;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistentObject;

/**
 * NullNode is an implementation of the NullObject pattern,
 * It inherits all functionality from Node (acts like a Node)
 * and is only distinguishable from a Node instance by it's class or oid.
 * If a Node's parent is a NullNode instance, than they should be separated
 * in the data store (e.g. the foreign key should be null, if allowed by the database).
 * NullNode child instances should be ignored.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullNode extends Node {

  /**
   * @see PersistentObject::getOID()
   */
  public function getOID() {
    return new ObjectId($this->getType(), null);
  }
}
?>