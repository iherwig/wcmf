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
 * Wrap a value inside a html tag, if the string is not empty
 *
 * Example:
 * @code
 * {$text|wrap:'<h1 class="display-1">'}
 * @endcode
 *
 * @param $string The string to wrap
 * @param $tag The opening tag
 * @return String
 */
function smarty_modifier_wrap($string, $tag) {
  $matches = [];
  if (preg_match('/<([a-zA-Z0-9]+)(\s.*?>|>)/', trim($tag), $matches)) {
    $element = $matches[1];
    return strlen($string) > 0 ? $tag.$string.'</'.$element.'>' : "";
  }
  return $string;
}
?>