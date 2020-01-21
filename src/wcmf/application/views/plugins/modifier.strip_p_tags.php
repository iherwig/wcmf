<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */

/**
 * Remove enclosing p tags from a string.
 *
 * Example:
 * @code
 * <p>{$text|strip_p_tags}</p>
 * @endcode
 *
 * @param $text The text
 * @return String
 */
function smarty_modifier_strip_p_tags($text) {
  return preg_replace('/^\s*<p>|<\/p>\s*$/', '', $text);
}
?>