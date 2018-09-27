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
 * Add enclosing p tags to a string, if not existing.
 * Replace newlines by br tags, if no p tags were existing.
 *
 * Example:
 * @code
 * <p>{$text|add_p_tags}</p>
 * @endcode
 *
 * @param $text The text
 * @return String
 */
function smarty_modifier_add_p_tags($text) {
  if (!preg_match('/^<p/', trim($text))) {
    $text = '<p>'.nl2br(trim($text)).'</p>';
  }
  return $text;
}
?>