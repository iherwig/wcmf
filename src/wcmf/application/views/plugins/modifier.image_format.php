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
 * Output the format ('portrait' or 'landscape') of the given image
 *
 * Example:
 * @code
 * <img src="{$image}" class="{$image|image_format}">
 * @endcode
 *
 * @param $image The path to the image
 * @return String
 */
function smarty_modifier_image_format($image) {
  if (strlen($image) > 0 && file_exists($image)) {
    $size = getimagesize($image);
    if ($size !== false) {
      return $size[0] > $size[1] ? 'landscape' : 'portrait';
    }
  }
  return '';
}
?>