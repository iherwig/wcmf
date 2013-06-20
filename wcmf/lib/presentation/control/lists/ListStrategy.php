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
namespace wcmf\lib\presentation\control\lists;

/**
 * ListStrategy defines the interface for classes that
 * retrieve value lists.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ListStrategy {

  /**
   * Get a list of key/value pairs defined by the given configuration.
   * @param configuration The list type specific configuration of the list as
   *                 used in the input_type definition
   * @param language The lanugage if the list should be localized. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return An assoziative array containing the key/value pairs
   */
  function getListMap($configuration, $language=null);
}
?>
