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

/**
 * Get the width of the given image in pixels
 *
 * Example:
 * @code
 * {if $image|width > 1024}
 *    ...handle big image...
 * {else}
 *    ...
 * {/if}
 * @endcode
 *
 * @param $image The image file
 * @param $halfsize Boolean whether the width should be divided by two for retina displays (optional, default: __false__)
 * @return Integer
 */
function smarty_modifier_width($image, $halfsize=false) {
  $size = getimagesize($image);
  return $halfsize ? intval($size[0]/2) : $size[0];
}
?>