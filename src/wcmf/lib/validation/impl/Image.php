<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\validation\impl;

use wcmf\lib\i18n\Message;
use wcmf\lib\io\FileUtil;
use wcmf\lib\validation\ValidateType;

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
   *    where each array entry is an array with the size as first value and an boolean
   *    indicating if the size should be matched exactly as second value
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
    $absValue = WCMF_BASE.$value;
    $value = FileUtil::fixFilename(FileUtil::realpath(dirname($absValue)).'/'.basename($absValue));
    if (!$value) {
      return false;
    }

    // check dimensions of the image
    list($width, $height) = @getimagesize($value);
    $widthOk = $imgWidth !== false ?
            ($imgWidth[1] && $imgWidth[0] == $width) || (!$imgWidth[1] && $imgWidth[0] <= $width) :
            true;
    $heightOk = $imgHeight !== false ?
            ($imgHeight[1] && $imgHeight[0] == $height) || (!$imgHeight[1] && $imgHeight[0] <= $height) :
            true;

    return $widthOk && $heightOk;
  }
}
?>
