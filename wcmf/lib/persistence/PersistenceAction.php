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
namespace wcmf\lib\persistence;

/**
 * PersistenceAction values are used to define actions on PersistentObject
 * instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceAction {

  const READ   = 'read';
  const MODIFY = 'modify';
  const DELETE = 'delete';
  const CREATE = 'create';
}
?>
