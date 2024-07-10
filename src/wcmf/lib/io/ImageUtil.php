<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\io;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\URIUtil;
use wcmf\lib\util\StringUtil;

if (!class_exists('\Intervention\Image\ImageManager')) {
    throw new \wcmf\lib\config\ConfigurationException(
            '\wcmf\lib\io\ImageUtil requires \Intervention\Image\ImageManager to resize images. '.
            'If you are using composer, add intervention/image '.
            'as dependency to your project');
}

/**
 * ImageUtil provides support for image handling.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ImageUtil {

  const IMAGE_CACHE_SECTION = 'images';

  const SUPPORTED_FORMATS = ['jpeg', 'webp', 'png', 'gif', 'avif', 'jpeg2000'];

  const DEFAULT_QUALITY = 85;

  private static $scriptDirAbs = null;

  /**
   * Create an HTML image tag using srcset and sizes attributes.
   * The image locations in the srcset attribute will point to the frontend cache directory
   * (_FrontendCache_ configuration section).
   * @param $imageFile The image file location inside the upload directory relative to the executed script
   * @param $widths Array of sorted width values to be used in the srcset attribute
   * @param $type Indicates how width values should be used (optional, default: w)
   *        - w: Values will be used as pixels, e.g. widths="1600,960" results in srcset="... 1600w, ... 960w"
   *        - x: Values will be used as pixel ratio, e.g. widths="1600,960" results in srcset="... 2x, ... 1x"
   * @param $sizes String of media queries to define image size in relation of the viewport (optional)
   * @param $formats Associative array of with format names ('jpeg', 'webp', 'png', 'gif', 'avif', 'jpeg2000') as keys and quality values as values (optional)
   * @param $useDataAttributes Boolean indicating whether to replace src, srcset, sizes by data-src, data-srcset, data-sizes (optional, default: __false__)
   * @param $alt Alternative text (optional)
   * @param $class Image class (optional)
   * @param $title Image title (optional)
   * @param $data Data attributes as key/value pairs
   * @param $width Width in pixels to output for the width attribute, the height attribute will be calculated according to the aspect ration (optional)
   * @param $fallbackFile The image file to use, if imageFile does not exist (optional)
   * @param $generate Boolean indicating whether to generate the images or not (optional, default: __false__)
   * @return string
   */
  public static function getImageTag($imageFile, $widths, $type='w', $sizes='', $formats=[],
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

    $srcset = [];

    // don't resize animated gifs
    $isAnimated = self::isAnimated($imageFile);
    if (!$isAnimated) {
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

      // skip processing for fallback image
      if ($imageFile != $fallbackFile) {
        // get file name and cache directory
        $baseName = FileUtil::basename($imageFile);
        $directory = self::getCacheDir($imageFile);

        // create the cache directory if requested
        if ($generate) {
          FileUtil::mkdirRec($directory);
        }

        // calculate sources for all formats
        // NOTE there will be at least one srcset called 'default'
        foreach (array_merge(['default' => null], $formats) as $format => $quality) {
          for ($i=0, $count=sizeof($widths); $i<$count; $i++) {
            $curWidth = intval($widths[$i]);
            if ($curWidth > 0) {
              $resizedFile = self::makeRelative($directory.self::makeFileName($baseName, $curWidth, $format == 'default' ? '' : $format, $quality));

              // create the cached file if requested
              if ($generate) {
                // only if the requested width is smaller than the image width
                if ($curWidth < $imageInfo[0]) {
                  // if the file does not exist in the cache or is older
                  // than the source file, we create it
                  $dateOrig = @filemtime($fixedFile);
                  $dateCache = @filemtime($resizedFile);
                  if (!file_exists($resizedFile) || $dateOrig > $dateCache) {
                    self::getCachedImage($resizedFile, true);
                  }

                  // fallback to source file, if cached file could not be created
                  if (!file_exists($resizedFile)) {
                    $resizedFile = $imageFile;
                  }
                }
              }

              if ($hasSrcSet) {
                // add to source set
                if (!isset($srcset[$format])) {
                  $srcset[$format] = [];
                }
                $srcset[$format][] = FileUtil::urlencodeFilename($resizedFile).' '.($type === 'w' ? $curWidth.'w' : ($count-$i).'x');
              }
              else if ($format == 'default') {
                // replace main source for single source entry
                $imageFile = $resizedFile;
              }
            }
          }
        }
      }
    }

    // collect image tag attributes
    $imageData = [
      'src' => FileUtil::urlencodeFilename($imageFile),
      'alt' => $alt,
      'class' => $class,
      'title' => $title,
    ];
    if ($width != null) {
      $imageData['width'] = intval($width);
      $imageData['height'] = intval($width * $imageInfo[1] / $imageInfo[0]);
    }
    if (isset($srcset['default']) && sizeof($srcset['default']) > 0) {
      $imageData['srcset'] = join(', ', $srcset['default']);
      if (strlen($sizes) > 0) {
        $imageData['sizes'] = $sizes;
      }
    }
    foreach ($data as $name => $value) {
      $imageData['data-'.$name] = str_replace('"', '\"', $value).'"';
    }

    $imageTag = '<img '.self::makeImageAttributeString($imageData, $useDataAttributes, null, ['src', 'alt']).'>';

    $tag = $imageTag;
    if (count($formats) > 0) {
      $tag = '<picture>';
      foreach ($formats as $format => $quality) {
        if (isset($srcset[$format])) {
          // update srcset with current format
          $imageData['srcset'] = join(', ', $srcset[$format]);
        }
        $tag .= '<source '.self::makeImageAttributeString($imageData, $useDataAttributes, ['srcset', 'sizes'], []).' type="image/'.$format.'">';
      }
      $tag .= $imageTag;
      $tag .= '</picture>';
    }

    return $tag;
  }

  /**
   * Output the cached image for the given cache location
   * @param $location
   * @param $returnLocation Boolean indicating if only the file location should be returned (optional)
   * @param $callback Function called, after the cached image is created, receives the original and cached image as parameters (optional)
   * @return string, if returnLocation is true
   */
  public static function getCachedImage($location, $returnLocation=false, $callback=null) {
    $location = rawurldecode($location);

    // strip the cache base from the location
    $cacheLocation = substr($location, strlen(self::IMAGE_CACHE_SECTION.'/'));

    // extract transformation information from the cache location
    $transform = self::extractTransformationInfo($cacheLocation);

    // get the original file location
    $sourceFile = self::getSourceDir($cacheLocation).$transform['basename'];

    // create the resized/re-encoded image file, if not existing
    $resizedFile = self::getCacheRoot().$cacheLocation;
    if (FileUtil::fileExists($sourceFile) && !FileUtil::fileExists($resizedFile)) {
      FileUtil::mkdirRec(pathinfo($resizedFile, PATHINFO_DIRNAME));
      $fixedFile = FileUtil::fixFilename($sourceFile);
      if ($transform['width'] !== null || $transform['type'] !== null || $transform['quality'] !== null) {
        self::resizeImage($fixedFile, $resizedFile, $transform['width'], $transform['type'], $transform['quality']);
      }
      else {
        // just copy in case of undefined width, type or quality
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
   * Get the cache location for the given image, width and optionally type and quality
   * @param $imageFile The image file location inside the upload directory relative to the executed script
   * @param $width
   * @param $type
   * @param $quality
   * @return string
   */
  public static function getCacheLocation($imageFile, $width, $type=null, $quality=null) {
    // don't change animated gifs
    if (self::isAnimated($imageFile)) {
      return $imageFile;
    }
    // get file name and cache directory
    $baseName = FileUtil::basename($imageFile);
    $directory = self::getCacheDir($imageFile);
    $filename = $directory.self::makeFileName($baseName, $width, $type, $quality);
    return self::makeRelative($filename);
  }

  /**
   * Extract width, height and original filename from the given file location.
   * The location is supposed to follow one of the following patterns:
   * - directory/{width}-basename
   * - directory/{width}-{type}-basename.{type}
   * - directory/{width}-{type@quality}-basename.{type}
   * - directory/{type}-basename.{type}
   * - directory/{type@quality}-basename.{type}
   * where width is a positive number and type is one of SUPPORTED_FORMATS optionally appended with a positive number indicating the quality value (@{quality})
   * @param $file
   * @return array{'width': int|null, 'type': string|null, 'quality': int|null, 'basename': string}
   */
  public static function extractTransformationInfo($file) {
    $basename = FileUtil::basename($file);
    $typesPattern = '/^('.join('|', self::SUPPORTED_FORMATS).')(@[0-9]+)?$/';

    // extract with and height and determine original basename
    $width = null;
    $type = null;
    $quality = null;
    $parts = explode('-', $basename);
    if (count($parts) > 0) {
      if (is_numeric($parts[0])) {
        $width = array_shift($parts);
        if (count($parts) > 0 && preg_match($typesPattern, $parts[0])) {
          [$type, $quality] = explode('@', array_shift($parts));
        }
      }
      else if (preg_match($typesPattern, $parts[0])) {
        [$type, $quality] = explode('@', array_shift($parts));
      }
      $basename = join('-', $parts);
      if ($type) {
        $basename = preg_replace('/\.'.$type.'$/', '', $basename);
      }
    }
    return ['width' => $width, 'type' => $type, 'quality' => $quality, 'basename' => $basename];
  }

  /**
   * Delete the cached images for the given image file
   * @param $imageFile The image file location inside the upload directory as stored in the database
   */
  public static function invalidateCache($imageFile) {
    if (strlen($imageFile) > 0) {
      $imageFile = URIUtil::makeRelative(URIUtil::makeAbsolute($imageFile, WCMF_BASE), self::getMediaRoot());

      // get file name and cache directory
      $baseName = FileUtil::basename($imageFile);
      $directory = self::getCacheDir($imageFile);

      // delete matches of the form ([0-9]+)-$fixedFile
      if (is_dir($directory)) {
        $typesPattern = '('.join('|', self::SUPPORTED_FORMATS).')(@[0-9]+)?';
        foreach (FileUtil::getFiles($directory) as $file) {
          $matches = [];
          // regex for matching the transformation prefix of the file ($matches[0])
          $prefixRegex = '/^(([0-9]+|'.$typesPattern.')(-'.$typesPattern.')?-)/';
          if (preg_match($prefixRegex, $file, $matches) && strpos($file, $matches[0].$baseName) === 0) {
            unlink($directory.$file);
          }
        }
      }
    }
  }

  private static function getImageManager() {
    $manager = new \Intervention\Image\ImageManager(
      new \Intervention\Image\Drivers\Gd\Driver()
    );
    return $manager;
  }

  /**
   * Get the cache directory for the given source image file
   * @param $imageFile
   * @return string
   */
  private static function getCacheDir($imageFile) {
    return self::getCacheRoot().dirname($imageFile).'/';
  }

  /**
   * Get the source directory for the given cached image location
   * @param $location
   * @return string
   */
  private static function getSourceDir($location) {
    return self::makeRelative(self::getMediaRoot()).dirname($location).'/';
  }

  /**
   * Get the absolute image cache root directory
   * @return string
   */
  private static function getCacheRoot() {
    $config = ObjectFactory::getInstance('configuration');
    return $config->getDirectoryValue('cacheDir', 'FrontendCache').self::IMAGE_CACHE_SECTION.'/';
  }

  /**
   * Get the absolute media root directory
   * @return string
   */
  private static function getMediaRoot() {
    // images are located in the common parent directory of the media and cache directory
    $config = ObjectFactory::getInstance('configuration');
    $mediaRootAbs = $config->getDirectoryValue('uploadDir', 'Media');
    $cacheRootAbs = $config->getDirectoryValue('cacheDir', 'FrontendCache');
    return StringUtil::longestCommonPrefix([$mediaRootAbs, $cacheRootAbs]);
  }

  /**
   * Make the current location relative to the executed script
   * @param $location
   * @return string
   */
  private static function makeRelative($location) {
    if (self::$scriptDirAbs == null) {
      self::$scriptDirAbs = dirname(FileUtil::realpath($_SERVER['SCRIPT_FILENAME'])).'/';
    }
    return URIUtil::makeRelative($location, self::$scriptDirAbs);
  }

  /**
   * Resize the given image to the given width and optionally type and quality
   * @param $sourceFile
   * @param $destFile
   * @param $width
   * @param $type Image type, if the image should be re-encoded to another format, (will be determined from image, if not specified)
   * @param $quality Quality percentage value (empty, if the quality should not be changed)
   */
  private static function resizeImage($sourceFile, $destFile, $width, $type=null, $quality=null) {
    $manager = self::getImageManager();
    $image = $manager->read($sourceFile);
    $image->scaleDown(width: $width);
    // re-encode if type or quality are specified
    if ($type || $quality) {
      $type = $type ?? mime_content_type($sourceFile);
      $quality = $quality ?? self::DEFAULT_QUALITY;
      $image = $image->encodeByMediaType('image/'.$type, quality: intval($quality));
    }
    $image->save($destFile);
  }

  /**
   * Check if an image file is animated
   * @param $imageFile
   * @return boolean
   */
  private static function isAnimated($imageFile) {
    if (!($fh = @fopen($imageFile, 'rb'))) {
      return false;
    }
    $count = 0;
    // An animated gif contains multiple "frames", with each frame having a header made up of:
    // * a static 4-byte sequence (\x00\x21\xF9\x04)
    // * 4 variable bytes
    // * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)

    // We read through the file til we reach the end of the file, or we've found
    // at least 2 frame headers
    while (!feof($fh) && $count < 2) {
      $chunk = fread($fh, 1024 * 100); //read 100kb at a time
      $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
    }

    fclose($fh);
    return $count > 1;
  }

  /**
   * Get the filename for the given image, width and optionally type and quality
   * @param $filename
   * @param $width
   * @param $type
   * @param $quality
   * @return string
   */
  public static function makeFileName($filename, $width, $type=null, $quality=null) {
    $hasWidth = strlen($width) > 0;
    $hasType = strlen($type) > 0;
    $hasQuality = strlen($quality) > 0;
    $typeAndQualityValues = [];
    if ($hasType) {
      $typeAndQualityValues[] = $type;
    }
    if ($hasQuality) {
      $typeAndQualityValues[] = $quality;
    }
    $typeAndQuality = join('@', $typeAndQualityValues);
    $hasTypeAndQuality = strlen($typeAndQuality) > 0;
    return ($hasWidth ? $width.'-'.($hasTypeAndQuality ? $typeAndQuality.'-' : '') : '').$filename.($hasType ? '.'.$type : '');
  }

  /**
   * Format the given data into HTML image attribute string
   */
  private static function makeImageAttributeString($data, $useDataAttributes, $includeAttributes=null, $mandatoryAttributes=null) {
    $possibleDataAttributes = ['src', 'srcset', 'sizes'];
    return trim(array_reduce(array_keys($data), function($result, $name) use ($includeAttributes, $data, $useDataAttributes, $mandatoryAttributes, $possibleDataAttributes) {
      $isValidAttribute = $includeAttributes == null || in_array($name, $includeAttributes);
      $isMandatoryAttribute = $mandatoryAttributes != null && in_array($name, $mandatoryAttributes);
      if ($isValidAttribute && ($isMandatoryAttribute || strlen($data[$name]) > 0)) {
        $attributeName = ($useDataAttributes && in_array($name, $possibleDataAttributes) ? 'data-' : '').$name;
        $result .= ' '.$attributeName.'="'.$data[$name].'"';
      }
      return $result;
    }, ''));
  }
}
?>
