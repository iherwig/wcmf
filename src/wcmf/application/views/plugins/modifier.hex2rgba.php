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
 * Convert a hex color value into rgba
 *
 * Example:
 * @code
 * {$hexColor|hex2rgba:"0.5"}
 * @endcode
 *
 * @param $hexColor The color value in hex notation
 * @param $alpha The alpha value (optional)
 * @return String
 */
function smarty_modifier_hex2rgba($hexColor, $alpha=null) {
  $hexColor = str_replace("#", "", $hexColor);

  if(strlen($hexColor) == 3) {
    $r = hexdec($hexColor[0].$hexColor[0]);
    $g = hexdec($hexColor[1].$hexColor[1]);
    $b = hexdec($hexColor[2].$hexColor[2]);
  }
  else {
    $r = hexdec($hexColor[0].$hexColor[1]);
    $g = hexdec($hexColor[2].$hexColor[3]);
    $b = hexdec($hexColor[4].$hexColor[5]);
  }
  return 'rgba('.$r.', '.$g.', '.$b.', '.($alpha !== null ? $alpha : '1.0').')';
}
?>