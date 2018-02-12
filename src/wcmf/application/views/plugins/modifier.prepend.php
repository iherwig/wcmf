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

/**
 * Prepend a value, if the string is not empty
 *
 * Example:
 * @code
 * {$text|prepend:"- "}
 * @endcode
 *
 * @param $string The string to prepend to
 * @param $prefix The value to prepend
 * @return String
 */
function smarty_modifier_prepend($string, $prefix) {
  return strlen($string) > 0 ? $prefix.$string : "";
}
?>