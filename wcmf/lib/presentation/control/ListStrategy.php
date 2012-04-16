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
namespace wcmf\lib\presentation\control;

/**
 * ListStrategy defines the interface for classes that
 * retrieve value lists.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ListStrategy {

  /**
   * Get a list of key/value pairs defined by the given description.
   * @param configuration The list type specific configuration of the list as
   *                 used in the input_type definition
   * @param value The selected value (maybe null, default: null)
   * @param nodeOid Serialized oid of the node containing this value (for determining remote oids) [default: null]
   * @param language The lanugage if Control should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return An assoziative array containing the key/value pairs
   */
  function getListMap($configuration, $value=null, $nodeOid=null, $language=null);
}
?>
