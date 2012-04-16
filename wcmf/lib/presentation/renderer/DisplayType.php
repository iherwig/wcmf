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
namespace wcmf\lib\presentation\renderer;

/**
 * DisplayType defines the interface for classes that render values
 * of a certain display type in html.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface DisplayType {

  /**
   * Render the given value.
   * @param value The value to display
   * @param attributes Additional optional attributes that will be interpreted
   *   by the concrete DisplayType instance
   * @return The HMTL representation of the value
   */
  function render($value, $attributes);
}
?>
