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

/**
 * Get the aspect ratio of the given image
 *
 * Example:
 * @code
 * {if $image|ratio < 1.78}
 *    ...aspect ratio of image is smaller than 16:9, e.g 4:3...
 * {else}
 *    ...
 * {/if}
 * @endcode
 *
 * @param $image The image file
 * @return Float
 */
function smarty_modifier_ratio($image) {
  $size = getimagesize($image);
  return $size[0]/$size[1];
}
?>
