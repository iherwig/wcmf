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
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\util\URIUtil;

if (!class_exists('\Eventviva\ImageResize')) {
    throw new ConfigurationException(
            'smarty_function_image requires '.
            'ImageResize to resize/crop images. If you are using composer, add eventviva/php-image-resize '.
            'as dependency to your project');
}

/**
 * Render an responsive image tag using srcset and sizes attributes.
 *
 * Example:
 * @code
 * {res_image src=$image->getFile() widths="1600,960,640" type="w"
 *          sizes="(min-width: 50em) 33vw, (min-width: 28em) 50vw, 100vw"
 *          alt="Image 1" default="images/blank.gif"}
 * @endcode
 *
 * @param $params Array with keys:
 *        - src: The image file
 *        - default: The default file, if src does not exist (optional)
 *        - widths: Comma separated, sorted list of width values to be used in the srcset attribute
 *        - type: Indicates how width values should be used (optional, default: w)
 *          - w: Values will be used as pixels, e.g. widths="1600,960" results in srcset="... 1600w, ... 960w"
 *          - x: Values will be used as pixel ration, e.g. widths="1600,960" results in srcset="... 2x, ... 1x"
 *        - sizes: Media queries to define image size in relation of the viewport (optional)
 *        - useDataAttributes: Boolean indicating whether to replace src, srcset, sizes by data-src, data-srcset, data-sizes (optional, default: false)
 *        - generate: Boolean indicating whether to generate the images or not (optional, default: false)
 *        - class: Image class (optional)
 *        - alt: Alternative text (optional)
 *        - title: Image title (optional)
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_image($params, Smarty_Internal_Template $template) {
  $file = $params['src'];
  $default = isset($params['default']) ? $params['default'] : '';
  $widths = $params['widths'];
  $type = isset($params['type']) ? $params['type'] : 'w';
  $sizes = isset($params['sizes']) ? $params['sizes'] : '';
  $useDataAttributes = isset($params['useDataAttributes']) ? $params['useDataAttributes'] : false;
  $generate = isset($params['generate']) ? $params['generate'] : false;
  $class = isset($params['class']) ? $params['class'] : '';
  $alt = isset($params['alt']) ? $params['alt'] : '';
  $title = isset($params['title']) ? $params['title'] : '';

  if (strlen($file) == 0 && strlen($default) == 0) {
    return;
  }

  $file = FileUtil::fixFilename($file);

  // check if the file exists
  if (!is_file($file)) {
    // try the default
    $file = $default;
    if (!is_file($file)) {
      return;
    }
  }

  // get the image size in order to see if we have to resize
  $imageInfo = getimagesize($file);
  if ($imageInfo == false) {
    // the file is no image
    return;
  }

  $config = ObjectFactory::getInstance('configuration');
  $cacheRootAbs = $config->getDirectoryValue('cacheDir', 'Media').'images/';
  if ($generate) {
    FileUtil::mkdirRec(pathinfo($cacheRootAbs.$file, PATHINFO_DIRNAME));
  }

  $extension = pathinfo($file, PATHINFO_EXTENSION);
  $baseName = $cacheRootAbs.preg_replace('/\.'.$extension.'$/', '', $file).'-';

  $srcset = array();
  $requestedWidths = array_map('trim', explode(',', $widths));
  for ($i=0, $count=sizeof($requestedWidths); $i<$count; $i++) {
    $width = $requestedWidths[$i];
    $destNameAbs = $baseName.$width.'.'.$extension;
    $destName = URIUtil::makeRelative($destNameAbs, dirname(FileUtil::realpath($_SERVER['SCRIPT_FILENAME'])).'/');

    // if 'width' differs from the image values, we have to resize the image
    if ($width < $imageInfo[0] && $generate) {
      // if the file does not exist in the cache, we have to create it
      $dateOrig = @filemtime($file);
      $dateCache = @filemtime($destName);
      if (!file_exists($destName) || $dateOrig > $dateCache) {
        $image = new \Eventviva\ImageResize($file);
        $image->resizeToWidth($width);
        $image->save($destName);
      }

      // fallback to file, if cached file could not be created
      if (!file_exists($destName)) {
        $destName = $file;
      }
    }
    $srcset[] = $destName.' '.($type === 'w' ? $width.'w' : ($count-$i).'x');
  }

  $tag = '<img'.
          ' '.($useDataAttributes ? 'data-' : '').'src="'.$file.'"'.
          ' '.($useDataAttributes ? 'data-' : '').'srcset="'.join(', ', $srcset).'"'.
          ' '.(strlen($sizes) > 0 ? ($useDataAttributes ? 'data-' : '').'sizes="'.$sizes.'"' : '').
          (strlen($class) > 0 ? ' class="'.$class.'"' : '').
          (strlen($alt) > 0 ? ' alt="'.$alt.'"' : '').
          (strlen($title) > 0 ? ' title="'.$title.'"' : '').
          '>';
  return $tag;
}
?>