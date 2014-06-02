<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\validator\impl;

use wcmf\lib\persistence\validator\ValidateType;
use wcmf\lib\util\GraphicsUtil;

/**
 * Image ValidateType validates an image value.
 *
 * @code
 * image:{"width":[200,1],"height":[100,0]}  // width exactly 200px, height less than 100px
 *
 * imagesize:{"height":[300,0]}  // arbitrary width, height exactly 300px
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Image implements ValidateType {

  /**
   * @see ValidateType::validate
   */
  public function validate($value, $options=null) {
    if (strlen($value) == 0) {
      return true;
    }

    $imageOptions = json_decode($options, true);
    $imgWidth = isset($imageOptions['width']) ? $imageOptions['width'] : false;
    $imgHeight = isset($imageOptions['height']) ? $imageOptions['height'] : false;

    if ($imgWidth === false && $imgHeight === false) {
      return true;
    }

    $graphicsUtil = new GraphicsUtil();

    if (!$graphicsUtil->isImage($value)) {
      return false;
    }

    // check dimensions of the image
    if ($imgWidth !== false) {
      $checkWidth = $graphicsUtil->isValidImageWidth($value, $imgWidth[0], $imgWidth[1]);
    }
    else {
      $checkWidth = true;
    }
    if ($imgHeight !== false) {
      $checkHeight = $graphicsUtil->isValidImageHeight($value, $imgHeight[0], $imgHeight[1]);
    }
    else {
      $checkHeight = true;
    }
    if(!($checkWidth && $checkHeight)) {
      return false;
    }
    return true;
  }
}
?>
