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
namespace wcmf\lib\persistence\validator\impl;

use wcmf\lib\persistence\validator\ValidateType;

/**
 * RegExp ValidateType validates against the given regular expression.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RegExp implements ValidateType {

  /**
   * @see ValidateType::validate
   */
  public function validate($value, $options=null) {
    return preg_match("/".$options."/m", $value);
  }
}
?>
