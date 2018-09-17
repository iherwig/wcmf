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
 * Output the cache location of the given image and width
 *
 * Example:
 * @code
 * <img src={$image|image_cache:800}">
 * @endcode
 *
 * @param $image The path to the image
 * @param $width
 * @return String
 */
use wcmf\lib\io\ImageUtil;

function smarty_modifier_image_cache($image, $width) {
  return ImageUtil::getCacheLocation($image, $width);
}
?>