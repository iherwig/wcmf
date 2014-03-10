<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\persistence\validator;

/**
 * ValidateType defines the interface for all validator classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ValidateType {

  /**
   * Validate a given value. The options format is type specific.
   * @param value The value to validate
   * @param options Optional options passed as a string
   * @return Boolean
   */
  function validate($value, $options=null);
}
?>
