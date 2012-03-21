<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
namespace wcmf\lib\presentation\smarty_plugins;

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
*           smarty_function_translate_url). The 'sizemode' parameter can have one of the followig values:
*           - resize: The browser scales the image to fit inside the given dimensions
*           - resample: The image will be physically scaled to fit inside the given dimensions
*           - crop: The image will be clipped from the middle to fit inside the given dimensions
*           If the parameter is not given, it defaults to resample.
* Usage:    {image src=$image->getFile() base="cms/application/" width="100" alt="Image 1" params='border="0"'
*           default="images/blank.gif" sizemode=resize}
* -------------------------------------------------------------
*/
function smarty_function_image($params, &$smarty)
{
  $file = $params['src'];
  $base = $params['base'];
  $default = $params['default'];
  $sizemode = $params['sizemode'];

  if (strlen($file) == 0 && strlen($default) == 0) {
    return;
  }

  // translate the file url using base
  if (isset($params['base']))
  {
    // translate file url
    $urls = URIUtil::translate($file, $base);
    $file = $urls['relative'];
    // translate default file url
    $urls = URIUtil::translate($default, $base);
    $default = $urls['relative'];
  }

  // check if the file exists
  if (!is_file($file))
  {
    // try the default
    $file = $default;
    if (!is_file($file)) {
      return;
    }
  }

  // get the image size in order to see if we have to resize
  $imageSize = getimagesize($file);
  if ($imageSize == false)
  {
    // the file is no image
    return;
  }

  $requestedWidth = isset($params['width']) ? $params['width']: null;
  $requestedHeight = isset($params['height']) ? $params['height']: null;

  if (strlen($sizemode) == 0) {
  	$sizemode = 'resample';
  }

  // don't resize big images, because of resource limits
  if (filesize($file) > 1500000) {
    $sizemode = 'resize';
  }

  if (($sizemode != 'resize') && ($requestedWidth != null || $requestedHeight != null) &&
    ($requestedWidth < $imageSize[0] || $requestedHeight < $imageSize[1]))
  {
    // if 'width' or 'height' are given and they differ from the image values,
    // we have to resize the image

    // get the file extension
    preg_match('/\.(\w+)$/', $file, $matches);
    $extension = $matches[1];

    $destName = $smarty->cache_dir.md5($file.filectime($file).$requestedWidth.$requestedHeight.$sizemode).'.'.$extension;

    // if the file does not exist in the cache, we have to create it
    $dateOrig = fileatime($file);
    $dateCache = fileatime($destName);
    if (!file_exists($destName) || $dateOrig > $dateCache)
    {
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
  if ($sizemode == 'resize')
  {
    if ($requestedWidth != null) {
      $widthStr = ' width="'.$requestedWidth.'px"';
    }
    if ($requestedHeight != null) {
      $heightStr = ' height="'.$requestedHeight.'px"';
    }
  }
  echo '<img src="'.$file.'"'.$widthStr.$heightStr.' alt="'.$params['alt'].'" '.$params['params'].'/>';
}
?>