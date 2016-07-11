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
namespace wcmf\lib\persistence\validator\impl;

use wcmf\lib\i18n\Message;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\validator\ValidateType;
use wcmf\lib\util\GraphicsUtil;

/**
 * Image validates an image value.
 *
 * Configuration examples:
 * @code
 * // width exactly 200px, height less than 100px
 * image:{"width":[200,1],"height":[100,0]}
 *
 * // arbitrary width, height exactly 300px
 * image:{"height":[300,0]}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Image implements ValidateType {

  /**
   * @see ValidateType::validate
   * $options is an associative array with keys 'width' (optional) and 'height' (optional)
   */
  public function validate($value, Message $message, $options=null) {
    if (strlen($value) == 0) {
      return true;
    }

    $imgWidth = isset($options['width']) ? $options['width'] : false;
    $imgHeight = isset($options['height']) ? $options['height'] : false;

    if ($imgWidth === false && $imgHeight === false) {
      return true;
    }

    // translate path
    $fileUtil = new FileUtil();
    $absValue = WCMF_BASE.$value;
    $value = $fileUtil->realpath(dirname($absValue)).'/'.basename($absValue);

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
