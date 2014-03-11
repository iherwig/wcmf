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
 * Filter ValidateType validates against the given php filter.
 * Example for using FILTER_VALIDATE_INT with min_range option
 * and FILTER_FLAG_ALLOW_HEX flag and FILTER_VALIDATE_REGEXP with
 * regexp option:
 *
 * @code
 * filter:int|{"options":{"min_range":0},"flags":2}
 *
 * filter:validate_regexp|{"options":{"regexp":"/^[0-9]*$/"}}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Filter implements ValidateType {

  /**
   * @see ValidateType::validate
   */
  public function validate($value, $options=null) {
    $filterDef = explode('|', $options, 2);
    $filterName = $filterDef[0];
    $filterOptions = sizeof($filterDef) > 1 ? json_decode($filterDef[1], true) : null;
    return filter_var($value, filter_id($filterName), $filterOptions) !== false;
  }
}
?>
