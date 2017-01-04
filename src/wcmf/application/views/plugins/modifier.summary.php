<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\util\StringUtil;

/**
 * Truncate a text while preserving words, removing line breaks, tags and
 * decoding html entities.
 *
 * Example:
 * @code
 * <meta name="description" content="{$text|summary}">
 * @endcode
 *
 * @param $text The text to truncate
 * @param $length The number of chars to truncate to (default: 150)
 * @param $suffix The suffix to append (default: …)
 * @return String
 */
function smarty_modifier_summary($text, $length=150, $suffix='…') {
  $text = trim(preg_replace("/ +/", " ", preg_replace("/[\r\n\t]/", " ", html_entity_decode(strip_tags($text)))));
  return StringUtil::cropString($text, $length, $suffix);
}
?>