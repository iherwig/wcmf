<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */

/**
 * Append a value, if the string is not empty
 *
 * Example:
 * @code
 * {$text|append:", "}
 * @endcode
 *
 * @param $string The string to append to
 * @param $suffix The value to append
 * @return String
 */
function smarty_modifier_append($string, $suffix) {
  return strlen($string) > 0 ? $string.$suffix : "";
}
?>