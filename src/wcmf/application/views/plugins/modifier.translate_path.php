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
use wcmf\lib\util\URIUtil;
use wcmf\lib\io\FileUtil;

/**
 * Translate the given path into a path relative to the executed script.
 *
 * Example:
 * @code
 * <a href="{$pdf|translate_path:$cmsBase}" target="_blank">Link</a>
 * @endcode
 *
 * @param $path The path
 * @param $base Relative path from the executed script to the location
 *                   that the given path is relative to.
 * @param $strict Boolean whether the function should return null, if the url does not exist or not (optiona, default: false)
 * @return String
 */
function smarty_modifier_translate_path($path, $base, $strict=false) {
  if (strlen($path) > 0) {
    $urls = URIUtil::translate($path, $base);
    return FileUtil::fileExists($urls['relative']) || !$strict ? $urls['relative'] : null;
  }
  return null;
}
?>