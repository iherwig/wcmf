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
 * PersistenceAction values are used to define actions on PersistentObject
 * instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceAction {

  const CREATE = 'create';
  const READ   = 'read';
  const UPDATE = 'update';
  const DELETE = 'delete';
}
?>
