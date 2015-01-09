<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\io\FileUtil;
use wcmf\lib\util\GraphicsUtil;
use wcmf\lib\util\URIUtil;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.image.php
* Type:     function
* Name:     image
* Purpose:  Renders an image tag, if the 'src' value points to an image file or the 'default' parameter is
*           given. If 'width' or 'height' are given, the image will be resized to that values. The resize method
*           depends on the 'sizemode' parameter. If the image size will be physically changed, a copy will be created
*           in the cache directory that is used by the View class, which means that invalidating the View
*           cache invalidates the image cache too. The content of the 'params' parameter will be put as is in the
*           created image tag. If the image url has to be translated, use the 'base' parameter (see
*           smarty_function_translate_url).
*           The 'sizemode' parameter can have one of the followig values:
*           - resize: The browser scales the image to fit inside the given dimensions
*           - resample: The image will be physically scaled to fit inside the given dimensions
*           - crop: The image will be clipped from the middle to fit inside the given dimensions
*           If the parameter is not given, it defaults to resample.
*           The 'valueMode' parameter can have one of the following values:
*           - fitInto: The image is resized if it's width or height exceeds one of the given values. Image proportions will be kept.
*           - scaleTo: The image is resized if it's width or height differs from the given values. Image proportions will be kept.
*           - default: The image is resized if it's width or height differs from the given values. Image proportions will be ignored.
*           The image will be resized according to the given sizemode. The image won't be cropped with 'valuemode' set to anything else than default.
*           If the parameter is not given, default is used. Size attributes may be skipped using the nosizeoutput parameter.
* Usage:    {image src=$image->getFile() base="cms/application/" width="100" alt="Image 1" params='border="0"'
*           default="images/blank.gif" sizemode="resize" nosizeoutput=true}
* -------------------------------------------------------------
*/
function smarty_function_image($params, \Smarty_Internal_Template $template) {
  $file = $params['src'];
  $default = isset($params['default']) ? $params['default'] : '';
  $sizemode = isset($params['sizemode']) ? $params['sizemode'] : 'resample';
  $valuemode = isset($params['valuemode']) ? $params['valuemode'] : 'default';
  $alt = isset($params['alt']) ? $params['alt'] : '';
  $imageParams = isset($params['params']) ? $params['params'] : '';

  if (strlen($file) == 0 && strlen($default) == 0) {
    return;
  }

  // translate the file url using base
  if (isset($params['base'])) {
    $base = $params['base'];
    // translate file url
    $urls = URIUtil::translate($file, $base);
    $file = $urls['relative'];
    // translate default file url
    $urls = URIUtil::translate($default, $base);
    $default = $urls['relative'];
  }

  // check if the file exists
  if (!is_file($file)) {
    // try the default
    $file = $default;
    if (!is_file($file)) {
      return;
    }
  }

  // get the image size in order to see if we have to resize
  $imageSize = getimagesize($file);
  if ($imageSize == false) {
    // the file is no image
    return;
  }

  $requestedWidth = isset($params['width']) ? $params['width']: null;
  $requestedHeight = isset($params['height']) ? $params['height']: null;

  // calculate new dimensions if value mode is set
  if ($valuemode == 'scaleTo' || $valuemode == 'fitInto') {
    if ($valuemode == 'fitInto' && $requestedHeight && $requestedHeight > $imageSize[1]) {
      // if image should fit into a rectangle and it's height is smaller than the requested, leave image untouched
      $requestedHeight = $imageSize[1];
    }
    else if ($valuemode == 'fitInto' && $requestedWidth && $requestedWidth > $imageSize[0]) {
      // if image should fit into a rectangle and it's width is smaller than the requested, leave image untouched
      $requestedWidth = $imageSize[0];
    }
    if ($requestedHeight == null) {
      // calculate height if only width is given
      $requestedHeight = floor(($imageSize[1] * $requestedWidth) / $imageSize[0]);
    }
    else if ($requestedWidth == null) {
      // calculate width if only height is given
      $requestedWidth = floor(($imageSize[0] * $requestedHeight) / $imageSize[1]);
    }
    else {
      // calculate either width or height depending on the ratio
      $requestedAspectRatio = $requestedHeight / $requestedWidth;
      $imageAspectRatio = $imageSize[1] / $imageSize[0];
      if ($requestedAspectRatio >= $imageAspectRatio) {
        // scale based on width, keep requestedWidth
        $requestedHeight = ($imageSize[1] * $requestedWidth) / $imageSize[0];
      }
      else {
        // scale based on height, keep requestedHeight
        $requestedWidth = ($imageSize[0] * $requestedHeight) / $imageSize[1];
      }
    }
  }

  // don't resize big images, because of resource limits
  if (filesize($file) > 2500000) {
    $sizemode = 'resize';
  }

  if (($sizemode != 'resize') && ($requestedWidth != null || $requestedHeight != null) &&
    ($requestedWidth < $imageSize[0] || $requestedHeight < $imageSize[1])) {
    // if 'width' or 'height' are given and they differ from the image values,
    // we have to resize the image

    // get the file extension
    preg_match('/\.(\w+)$/', $file, $matches);
    $extension = $matches[1];

    $destNameAbs = $template->cache_dir.md5($file.filectime($file).$requestedWidth.$requestedHeight.$sizemode).'.'.$extension;
    $destName = URIUtil::makeRelative($destNameAbs, dirname(FileUtil::realpath($_SERVER['SCRIPT_FILENAME'])).'/');

    // if the file does not exist in the cache, we have to create it
    $dateOrig = @fileatime($file);
    $dateCache = @fileatime($destName);
    if (!file_exists($destName) || $dateOrig > $dateCache) {
      $graphicsUtil = new GraphicsUtil();
      if ($sizemode == 'resample') {
        $graphicsUtil->createThumbnail($file, $destName, $requestedWidth, $requestedHeight);
      }
      else {
        $graphicsUtil->cropImage($file, $destName, $requestedWidth, $requestedHeight);
      }
    }

    // use the cached file
    if (file_exists($destName)) {
      $file = $destName;
    }
  }

  $widthStr = "";
  $heightStr = "";
  if (!isset($params['nosizeoutput']) || $params['nosizeoutput'] == false) {
    if ($requestedWidth != null) {
      $widthStr = ' width="'.$requestedWidth.'"';
    }
    if ($requestedHeight != null) {
      $heightStr = ' height="'.$requestedHeight.'"';
    }
  }

  echo '<img src="'.$file.'"'.$widthStr.$heightStr.' alt="'.$alt.'" '.$imageParams.'>';
}
?>