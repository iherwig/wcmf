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
 * Get the height of the given image in pixels
 *
 * Example:
 * @code
 * {if $image|height > 768}
 *    ...handle big image...
 * {else}
 *    ...
 * {/if}
 * @endcode
 *
 * @param $image The image file
 * @param $halfsize Boolean whether the height should be divided by two for retina displays (optional, default: __false__)
 * @return Integer
 */
function smarty_modifier_height($image, $halfsize=false) {
  $size = getimagesize($image);
  return $halfsize ? intval($size[1]/2) : $size[1];
}
?>