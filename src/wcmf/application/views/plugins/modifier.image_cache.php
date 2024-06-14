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
 * Output the cache location of the given image and width, type and quality
 *
 * Example:
 * @code
 * <img src={$image|image_cache:800}">
 * <img src={$image|image_cache:800:'webp'}">
 * <img src={$image|image_cache:800:'webp':85}">
 * @endcode
 *
 * @param $image The path to the image
 * @param $width
 * @param $type
 * @param $quality
 * @return String
 */
use wcmf\lib\io\ImageUtil;

function smarty_modifier_image_cache($image, $width, $type=null, $quality=null) {
  return ImageUtil::getCacheLocation($image, $width, $type, $quality);
}
?>