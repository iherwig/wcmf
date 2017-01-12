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
 * Prevent caching of a response by adding a unique parameter to the url.
 *
 * Example:
 * @code
 * {$url|prevent_cache}
 *
 * {$url|prevent_cache:"ts"}
 * @endcode
 *
 * @param $url The url
 * @param $name The parameter name (optional, default: unique)
 */
function smarty_modifier_prevent_cache($url, $name='unique') {
  return $url.'?'.$name.'='.uniqid((double)microtime()*1000000, 1);
}
?>