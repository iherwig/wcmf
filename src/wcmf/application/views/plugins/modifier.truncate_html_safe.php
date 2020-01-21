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
use wcmf\lib\util\StringUtil;

/**
 * Truncate a text while preserving words and html tags.
 *
 * Example:
 * @code
 * <p class="teaser">{$text|truncate_html_safe}</p>
 * @endcode
 *
 * @param $text The text to truncate
 * @param $length The number of chars to truncate to (default: 100)
 * @param $suffix The suffix to append (default: …)
 * @return String
 */
function smarty_modifier_truncate_html_safe($text, $length=100, $suffix='…') {
  return StringUtil::cropString($text, $length, $suffix);
}
?>