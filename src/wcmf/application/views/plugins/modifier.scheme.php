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
 * Add the scheme to an url, if missing.
 *
 * Example:
 * @code
 * <a href="{$url|scheme}">Link</a>
 * @endcode
 *
 * @param $url The url
 * @return String
 */
function smarty_modifier_scheme($url) {
  return (!preg_match('/^[a-zA-Z0-9-]+:\/\//', $url)) ? '//'.$url : $url;
}
?>