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
namespace wcmf\lib\io;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\URIUtil;

if (!class_exists('\Eventviva\ImageResize')) {
    throw new ConfigurationException(
            'ImageUtil requires ImageResize to resize images. '.
            'If you are using composer, add eventviva/php-image-resize '.
            'as dependency to your project');
}

/**
 * ImageUtil provides support for image handling.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ImageUtil {

  const IMAGE_CACHE_SECTION = 'images';

  private static $scriptDirAbs = null;

  /**
   * Create an HTML image tag using srcset and sizes attributes. The image locations
   * in the srcset attribute will point to the frontend cache directory
   * (_FrontendCache_ configuration section).
   * @param $imageFile The image file
   * @param $widths Array of sorted width values to be used in the srcset attribute
   * @param $type Indicates how width values should be used (optional, default: w)
   *        - w: Values will be used as pixels, e.g. widths="1600,960" results in srcset="... 1600w, ... 960w"
   *        - x: Values will be used as pixel ration, e.g. widths="1600,960" results in srcset="... 2x, ... 1x"
   * @param $sizes String of media queries to define image size in relation of the viewport (optional)
   * @param $useDataAttributes Boolean indicating whether to replace src, srcset, sizes by data-src, data-srcset, data-sizes (optional, default: __false__)
   * @param $alt Alternative text (optional)
   * @param $class Image class (optional)
   * @param $title Image title (optional)
   * @param $fallbackFile The image file to use, if imageFile does not exist (optional)
   * @param $generate Boolean indicating whether to generate the images or not (optional, default: __false__)
   * @return String
   */
  public static function getImageTag($imageFile, $widths, $type='w', $sizes='',
          $useDataAttributes=false, $alt='', $class='', $title='', $fallbackFile='',
          $generate=false) {
    // check if the image files exist
    if (!FileUtil::fileExists($imageFile)) {
      // try the fallback
      $imageFile = $fallbackFile;
      if (!FileUtil::fileExists($imageFile)) {
        return '';
      }
    }

    $fixedFile = FileUtil::fixFilename($imageFile);

    // get the image size in order to see if we have to resize
    $imageInfo = getimagesize($fixedFile);
    if ($imageInfo == false) {
      // the file is no image
      return '';
    }

    // skip srcset for fallback image
    $srcset = [];
    if ($imageFile != $fallbackFile) {
      // get file name and cache directory
      $baseName = basename($imageFile);
      $directory = self::getCacheDir($imageFile);

      // create the cache directory if requested
      if ($generate) {
        FileUtil::mkdirRec($directory);
      }

      // create srcset entries
      for ($i=0, $count=sizeof($widths); $i<$count; $i++) {
        $width = $widths[$i];
        $resizedFile = self::makeRelative($directory.$width.'-'.$baseName);

        // create the cached file if requested
        if ($generate) {
          // only if the requested width is smaller than the image width
          if ($width < $imageInfo[0]) {
            // if the file does not exist in the cache or is older
            // than the source file, we create it
            $dateOrig = @filemtime($fixedFile);
            $dateCache = @filemtime($resizedFile);
            if (!file_exists($resizedFile) || $dateOrig > $dateCache) {
              self::resizeImage($fixedFile, $resizedFile, $width);
            }

            // fallback to source file, if cached file could not be created
            if (!file_exists($resizedFile)) {
              $resizedFile = $imageFile;
            }
          }
        }
        $srcset[] = preg_replace(['/ /', '/,/'], ['%20', '%2C'], $resizedFile).
                ' '.($type === 'w' ? $width.'w' : ($count-$i).'x');
      }
    }

    $tag = '<img '.($useDataAttributes ? 'data-' : '').'src="'.$imageFile.'" alt="'.$alt.'"'.
      (strlen($class) > 0 ? ' class="'.$class.'"' : '').
      (strlen($title) > 0 ? ' title="'.$title.'"' : '');
    if (sizeof($srcset) > 0) {
      $tag .= ' '.($useDataAttributes ? 'data-' : '').'srcset="'.join(', ', $srcset).'"'.
        ' '.(strlen($sizes) > 0 ? ($useDataAttributes ? 'data-' : '').'sizes="'.$sizes.'"' : '');
    }
    $tag .= '>';
    return $tag;
  }

  /**
   * Output the cached image for the given cache location
   * @param $location
   */
  public static function getCachedImage($location) {
    // strip the cache base from the location
    $cacheLocation = substr($location, strlen(self::IMAGE_CACHE_SECTION.'/'));

    // determine the width and source file from the location
    // the location is supposed to follow the pattern directory/{width}-basename
    $basename = basename($cacheLocation);
    if(preg_match('/^([0-9]+)-/', $basename, $matches)) {
      $width = $matches[1];
      // remove width from location
      $basename = preg_replace('/^'.$width.'-/', '', $basename);
      $sourceFile = self::getSourceDir($cacheLocation).$basename;
    }
    else {
      $sourceFile = $cacheLocation;
    }

    // create the resized image file, if not existing
    $resizedFile = self::getCacheRoot().$cacheLocation;
    if (FileUtil::fileExists($sourceFile) && !FileUtil::fileExists($resizedFile)) {
      $fixedFile = FileUtil::fixFilename($sourceFile);
      self::resizeImage($fixedFile, $resizedFile, $width);
    }

    // return the image file
    $file = FileUtil::fileExists($resizedFile) ? $resizedFile : $sourceFile;
    $imageInfo = getimagesize($file);
    $image = file_get_contents($file);
    header('Content-type: '.$imageInfo['mime'].';');
    header("Content-Length: ".strlen($image));
    echo $image;
  }

  /**
   * Get the cache directory for the given source image file
   * @param $imageFile
   * @return String
   */
  private static function getCacheDir($imageFile) {
    $mediaRoot = self::getMediaRootRelative();
    return self::getCacheRoot().dirname(substr($imageFile, strlen($mediaRoot))).'/';
  }

  /**
   * Get the source directory for the given cached image location
   * @param $location
   * @return String
   */
  private static function getSourceDir($location) {
    return self::getMediaRootRelative().dirname($location).'/';
  }

  /**
   * Get the absolute image cache root directory
   * @return String
   */
  private static function getCacheRoot() {
    $config = ObjectFactory::getInstance('configuration');
    return $config->getDirectoryValue('cacheDir', 'FrontendCache').self::IMAGE_CACHE_SECTION.'/';
  }

  /**
   * Get the media root directory relative to the executed script
   * @return String
   */
  private static function getMediaRootRelative() {
    $config = ObjectFactory::getInstance('configuration');
    $mediaRootAbs = $config->getDirectoryValue('uploadDir', 'Media');
    return self::makeRelative($mediaRootAbs);
  }

  /**
   * Make the current location relative to the executed script
   * @param $location
   * @return String
   */
  private static function makeRelative($location) {
    if (self::$scriptDirAbs == null) {
      self::$scriptDirAbs = dirname(FileUtil::realpath($_SERVER['SCRIPT_FILENAME'])).'/';
    }
    return URIUtil::makeRelative($location, self::$scriptDirAbs);
  }

  /**
   * Resize the given image to the given width
   * @param $sourceFile
   * @param $destFile
   * @param $width
   */
  private static function resizeImage($sourceFile, $destFile, $width) {
    FileUtil::mkdirRec(pathinfo($destFile, PATHINFO_DIRNAME));
    $image = new \Eventviva\ImageResize($sourceFile);
    $image->resizeToWidth($width);
    $image->save($destFile);
  }
}
?>
