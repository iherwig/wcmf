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
namespace wcmf\lib\io;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\URIUtil;

if (!class_exists('\Gumlet\ImageResize')) {
    throw new ConfigurationException(
            'ImageUtil requires ImageResize to resize images. '.
            'If you are using composer, add gumlet/php-image-resize '.
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
   * Create an HTML image tag using srcset and sizes attributes. The original image is supposed
   * to be located inside the upload directory of the application (_Media_ configuration section).
   * The image locations in the srcset attribute will point to the frontend cache directory
   * (_FrontendCache_ configuration section).
   * @param $imageFile The image file location relative to the upload directory
   * @param $widths Array of sorted width values to be used in the srcset attribute
   * @param $type Indicates how width values should be used (optional, default: w)
   *        - w: Values will be used as pixels, e.g. widths="1600,960" results in srcset="... 1600w, ... 960w"
   *        - x: Values will be used as pixel ration, e.g. widths="1600,960" results in srcset="... 2x, ... 1x"
   * @param $sizes String of media queries to define image size in relation of the viewport (optional)
   * @param $useDataAttributes Boolean indicating whether to replace src, srcset, sizes by data-src, data-srcset, data-sizes (optional, default: __false__)
   * @param $alt Alternative text (optional)
   * @param $class Image class (optional)
   * @param $title Image title (optional)
   * @param $data Data attributes as key/value pairs
   * @param $width Width in pixels to output for the width attribute, the height attribute will be calculated according to the aspect ration (optional)
   * @param $fallbackFile The image file to use, if imageFile does not exist (optional)
   * @param $generate Boolean indicating whether to generate the images or not (optional, default: __false__)
   * @return String
   */
  public static function getImageTag($imageFile, $widths, $type='w', $sizes='',
          $useDataAttributes=false, $alt='', $class='', $title='', array $data=[], $width=null, $fallbackFile='',
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

    // create src entries
    $hasSrcSet = sizeof($widths) > 0;
    $widths = $hasSrcSet ? $widths : [$width];
    $srcset = [];

    // skip processing for fallback image
    if ($imageFile != $fallbackFile) {
      // get file name and cache directory
      $baseName = FileUtil::basename($imageFile);
      $directory = self::getCacheDir($imageFile);

      // create the cache directory if requested
      if ($generate) {
        FileUtil::mkdirRec($directory);
      }

      for ($i=0, $count=sizeof($widths); $i<$count; $i++) {
        $curWidth = intval($widths[$i]);
        if ($curWidth > 0) {
          $resizedFile = self::makeRelative($directory.$curWidth.'-'.$baseName);

          // create the cached file if requested
          if ($generate) {
            // only if the requested width is smaller than the image width
            if ($curWidth < $imageInfo[0]) {
              // if the file does not exist in the cache or is older
              // than the source file, we create it
              $dateOrig = @filemtime($fixedFile);
              $dateCache = @filemtime($resizedFile);
              if (!file_exists($resizedFile) || $dateOrig > $dateCache) {
                self::resizeImage($fixedFile, $resizedFile, $curWidth);
              }

              // fallback to source file, if cached file could not be created
              if (!file_exists($resizedFile)) {
                $resizedFile = $imageFile;
              }
            }
          }

          if ($hasSrcSet) {
            // add to source set
            $srcset[] = FileUtil::urlencodeFilename($resizedFile).' '.($type === 'w' ? $curWidth.'w' : ($count-$i).'x');
          }
          else {
            // replace main source for single source entry
            $imageFile = $resizedFile;
          }
        }
      }
    }

    $tag = '<img '.($useDataAttributes ? 'data-' : '').'src="'.FileUtil::urlencodeFilename($imageFile).'" alt="'.$alt.'"'.
      (strlen($class) > 0 ? ' class="'.$class.'"' : '').
      (strlen($title) > 0 ? ' title="'.$title.'"' : '');
    foreach ($data as $name => $value) {
      $tag .= ' data-'.$name.'="'.str_replace('"', '\"', $value).'"';
    }
    if (sizeof($srcset) > 0) {
      $tag .= ' '.($useDataAttributes ? 'data-' : '').'srcset="'.join(', ', $srcset).'"'.
        ' '.(strlen($sizes) > 0 ? ($useDataAttributes ? 'data-' : '').'sizes="'.$sizes.'"' : '');
    }
    if ($width != null) {
      $width = intval($width);
      $height = intval($width * $imageInfo[1] / $imageInfo[0]);
      $tag .= ' width="'.$width.'" height="'.$height.'"';
    }
    $tag = trim($tag).'>';
    return $tag;
  }

  /**
   * Output the cached image for the given cache location
   * @param $location
   * @param $returnLocation Boolean indicating if only the file location should be returned (optional)
   * @param $callback Function called, after the cached image is created, receives the original and cached image as parameters (optional)
   * @return String, if returnLocation is true
   */
  public static function getCachedImage($location, $returnLocation=false, $callback=null) {
    $location = rawurldecode($location);

    // strip the cache base from the location
    $cacheLocation = substr($location, strlen(self::IMAGE_CACHE_SECTION.'/'));

    // determine the width and source file from the location
    // the location is supposed to follow the pattern directory/{width}-basename
    $width = null;
    $basename = FileUtil::basename($cacheLocation);
    if (preg_match('/^([0-9]+)-/', $basename, $matches)) {
      // get required width from location and remove it from location
      $width = $matches[1];
      $basename = preg_replace('/^'.$width.'-/', '', $basename);
    }
    $sourceFile = self::getSourceDir($cacheLocation).$basename;

    // create the resized image file, if not existing
    $resizedFile = self::getCacheRoot().$cacheLocation;
    if (FileUtil::fileExists($sourceFile) && !FileUtil::fileExists($resizedFile)) {
      FileUtil::mkdirRec(pathinfo($resizedFile, PATHINFO_DIRNAME));
      $fixedFile = FileUtil::fixFilename($sourceFile);
      if ($width !== null) {
        self::resizeImage($fixedFile, $resizedFile, $width);
      }
      else {
        // just copy in case of undefined width
        copy($fixedFile, $resizedFile);
      }
      if (is_callable($callback)) {
        $callback($fixedFile, $resizedFile);
      }
    }

    // return the image file
    $file = FileUtil::fileExists($resizedFile) ? $resizedFile : (FileUtil::fileExists($sourceFile) ? $sourceFile : null);
    if ($returnLocation) {
      return $file;
    }
    $imageInfo = getimagesize($file);
    $image = file_get_contents($file);
    header('Content-type: '.$imageInfo['mime'].';');
    header("Content-Length: ".strlen($image));
    echo $image;
  }

  /**
   * Get the cache location for the given image and width
   * @param $imageFile Image file located inside the upload directory of the application given as path relative to WCMF_BASE
   * @param $width
   * @return String
   */
  public static function getCacheLocation($imageFile, $width) {
    // get file name and cache directory
    $baseName = FileUtil::basename($imageFile);
    $directory = self::getCacheDir($imageFile);
    return self::makeRelative($directory.(strlen($width) > 0 ? $width.'-' : '').$baseName);
  }

  /**
   * Delete the cached images for the given image file
   * @param $imageFile Image file located inside the upload directory of the application given as path relative to WCMF_BASE
   */
  public static function invalidateCache($imageFile) {
    if (strlen($imageFile) > 0) {
      $imageFile = URIUtil::makeRelative($imageFile, self::getMediaRootRelative());
      $fixedFile = FileUtil::fixFilename($imageFile);

      // get file name and cache directory
      $baseName = FileUtil::basename($imageFile);
      $directory = self::getCacheDir($imageFile);

      // delete matches of the form ([0-9]+)-$fixedFile
      if (is_dir($directory)) {
        foreach (FileUtil::getFiles($directory) as $file) {
          $matches = [];
          if (preg_match('/^([0-9]+)-/', $file, $matches) && $matches[1].'-'.$baseName === $file) {
            unlink($directory.$file);
          }
        }
      }
    }
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
    $image = new \Gumlet\ImageResize($sourceFile);
    $image->resizeToWidth($width);
    $image->save($destFile);
  }
}
?>
