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
namespace wcmf\lib\util;

use PHPImageWorkshop\ImageWorkshop;
use GifFrameExtractor\GifFrameExtractor;
use GifCreator\GifCreator;

if (!class_exists('PHPImageWorkshop\ImageWorkshop') ||
        !class_exists('GifFrameExtractor\GifFrameExtractor') ||
        !class_exists('GifCreator\GifCreator')) {
    throw new \wcmf\lib\config\ConfigurationException(
            'wcmf\lib\util\GraphicsUtil requires '.
            'ImageWorkshop, GifFrameExtractor and GifCreator. If you are using composer, '.
            'add sybio/image-workshop, sybio/gif-frame-extractor and sybio/gif-creator '.
            'as dependency to your project');
}

/**
 * GraphicsUtil provides support for graphic manipulation.
 *
 * @note This class requires ImageWorkshop, GifFrameExtractor and GifCreator
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class GraphicsUtil {

  private $errorMsg = '';

  /**
   * Get last error message.
   * @return The error string
   */
  public function getErrorMsg() {
    return $this->errorMsg;
  }

  /**
   * Check if a given file is an image.
   * @param $imgname Name of the imagefile to check
   * @return Boolean whether the file is an image
   */
  public function isImage($imgname) {
    try {
      ImageWorkshop::initFromPath($imgname);
      return true;
    } catch (\Exception $ex) {
      return false;
    }
  }

  /**
   * Check image dimensions.
   * @param $imgname Name of the imagefile to check
   * @param $width Width of the image, -1 means don't care
   * @param $height Height of the image, -1 means don't care
   * @param $exact Boolean whether the image should match the dimension exactly or might be smaller (default: _true_)
   * @return Boolean whether the image meets the dimensions, error string provided by getErrorMsg()
   */
  public function isValidImageDimension($imgname, $width, $height, $exact=true) {
    $widthOk = ($width == -1) || $this->isValidImageWidth($imgname, $width, $exact);
    $heightOk = ($height == -1) || $this->isValidImageHeight($imgname, $height, $exact);
    return ($widthOk && $heightOk);
  }

  /**
   * Check image width.
   * @param $imgname Name of the imagefile to check
   * @param $width Width of the image
   * @param $exact Boolean whether the image width should match exactly or might be smaller (default: _true_)
   * @return Boolean whether the image width meets the criteria, error string provided by getErrorMsg()
   * @note This method returns true if the file does not exist.
   */
  public function isValidImageWidth($imgname, $width, $exact=true) {
    try {
      $image = ImageWorkshop::initFromPath($imgname);
    } catch (\Exception $ex) {
      return true;
    }
    $imgWitdh = $image->getWidth();
    $dimOk = ($exact && $imgWitdh == $width) || (!$exact && $imgWitdh <= $width);
    if (!$dimOk) {
      $message = ObjectFactory::getInstance('message');
      $constraint = $exact ? $message->getText("exactly") : $message->getText("smaller than");
      $this->errorMsg = $message->getText("Wrong image width. Image width must be %1% %2%px - actual image width is %3%px.",
        array($constraint, $width, $imgWitdh));
      $this->errorMsg .= "\n";
    }
    return $dimOk;
  }

  /**
   * Check image height.
   * @param $imgname Name of the imagefile to check
   * @param $height Height of the image
   * @param $exact Boolean whether the image height should match exactly or might be smaller (default: _true_)
   * @return Boolean whether the image width meets the criteria, error string provided by getErrorMsg()
   * @note This method returns true if the file does not exist.
   */
  public function isValidImageHeight($imgname, $height, $exact=true) {
    try {
      $image = ImageWorkshop::initFromPath($imgname);
    } catch (\Exception $ex) {
      return true;
    }
    $imgHeight = $image->getHeight();
    $dimOk = ($exact && $imgHeight == $height) || (!$exact && $imgHeight <= $height);
    if (!$dimOk) {
      $message = ObjectFactory::getInstance('message');
      $constraint = $exact ? $message->getText("exactly") : $message->getText("smaller than");
      $this->errorMsg .= $message->getText("Wrong image height. Image height must be %1% %2%px - actual image height is %3%px.",
        array($constraint, $height, $imgHeight));
      $this->errorMsg .= "\n";
    }
    return $dimOk;
  }

  /**
   * Calculate image dimension to fit into a square, preserving the aspect ratio
   * @param $srcName The source file name
   * @param $maxDimension The maximum dimension the image should have (either width or height)
   * @return Array with width and height value or null, on error, error string provided by getErrorMsg()
   */
  public function fitIntoSquare($srcName, $maxDimension) {
    try {
      $image = ImageWorkshop::initFromPath($srcName);
      $sourceWidth = $image->getWidth();
      $sourceHeight = $image->getHeight();
      if ($sourceWidth < $sourceHeight) {
        $height = $maxDimension;
        $width = floor($sourceWidth*$height/$sourceHeight);
      }
      else {
        $width = $maxDimension;
        $height = floor($sourceHeight*$width/$sourceWidth);
      }
      // if image is actually smaller, leave small
      if ($width > $sourceWidth && $height > $sourceHeigth) {
        $width = $sourceWidth;
        $height = $sourceHeight;
      }
      return array($width, $height);
    } catch (\Exception $ex) {
      $this->errorMsg = $ex->getMessage();
      return null;
    }
  }

  /**
   * Create a thumbnail of an image file.
   * @param $srcName The source file name
   * @param $destName The destination file name
   * @param $width The width of the thumbnail (maybe null)
   * @param $height The height of the thumbnail (maybe null)
   * @return Boolean whether the operation succeeded, error string provided by getErrorMsg()
   * @note: supported image formats are GIF, JPG, PNG
   *        if only width or height are given the other dimension is calculated to preserve the aspect
   */
  public function createThumbnail($srcName, $destName, $width, $height) {
    try {
      $keepAspect = $width === null || $height === null;
      $this->processImageFunction($srcName, $destName, "resizeInPixel", array($width, $height, $keepAspect));
      return true;
    } catch (\Exception $ex) {
      $this->errorMsg = $ex->getMessage();
      return false;
    }
  }

  /**
   * Crop an image to the given size starting from the middle of a given start point.
   * @param $srcName The source file name
   * @param $destName The destination file name
   * @param $width The width of the cropped image (maybe null)
   * @param $height The height of the cropped image (maybe null)
   * @param $x The start point x coordinate (maybe null, default null)
   * @param $y The start point y coordinate (maybe null, default null)
   * @return Boolean whether the operation succeeded, error string provided by getErrorMsg()
   * @note: supported image formats are GIF, JPG, PNG
   *        if only width or height are given the other dimension is taken from the original image
   */
  public function cropImage($srcName, $destName, $width, $height, $x=null, $y=null) {
    try {
      // calculate parameters based on image (or first frame of gif animation)
      $image = ImageWorkshop::initFromPath($srcName);
      list($width, $height) = $this->calculateSizeParams($width, $height, $image->getWidth(), $image->getHeight());
      $x = ($x === null) ? $image->getWidth()/2 : $x;
      $y = ($y === null) ? $image->getHeight()/2 : $y;

      $this->processImageFunction($srcName, $destName, "cropInPixel", array($width, $height, $x, $y, 'LT'));
      return true;
    } catch (\Exception $ex) {
      $this->errorMsg = $ex->getMessage();
      return false;
    }
  }

  /**
   * Create a black and white copy of an image.
   * @param $srcName The source file name
   * @param $destName The destination file name
   */
  public function createBlackWhiteImage($srcName, $destName) {
    try {
      $this->processImageFunction($srcName, $destName, "applyFilter", array(IMG_FILTER_GRAYSCALE));
      return true;
    } catch (\Exception $ex) {
      $this->errorMsg = $ex->getMessage();
      return false;
    }
  }

  /**
   * Process the given function on the given source image (supports animated gifs)
   * and save the result in the given destination image.
   * @param $srcName The source file name
   * @param $destName The destination file name
   * @param $function The name of the function
   * @param $params The paremeters to be passed to the function
   */
  public function processImageFunction($srcName, $destName, $function, $params) {
    if (GifFrameExtractor::isAnimatedGif($srcName)) {
      // for animated gifs we need to process each frame
      $gfe = new GifFrameExtractor();
      $frames = $gfe->extract($srcName);
      $retouchedFrames = array();
      foreach ($frames as $frame) {
        $frameLayer = ImageWorkshop::initFromResourceVar($frame['image']);
        call_user_func_array(array($frameLayer, $function), $params);
        $retouchedFrames[] = $frameLayer->getResult();
      }
      $gc = new GifCreator();
      $gc->create($retouchedFrames, $gfe->getFrameDurations(), 0);
      file_put_contents($destName, $gc->getGif());
    }
    else {
      // all other images
      $image = ImageWorkshop::initFromPath($srcName);
      call_user_func_array(array($image, $function), $params);
      $image->save(dirname($destName), basename($destName), true, null, 100);
    }
  }

  /**
   * Render a text to an image. Using the default parameters the text will
   * be rendered into a box that fits the text. If the width parameter is not null and the
   * text exceeds the width, the text will be wrapped and the height parameter will be
   * used as lineheight.
   * Wrapping code is from http://de.php.net/manual/de/function.imagettfbbox.php#60673
   * @param $text The text to render
   * @param $fontfile The ttf font file to use
   * @param $fontsize The font size to use (in pixels)
   * @param $color The color to use for the text (as HEX value)
   * @param $bgcolor The color to use for the background (as HEX value)
   * @param $filename The name of the file to write to
   * @param $width The width of the image (or null if it should fit the text) (default: _null_)
   * @param $height The height of the image (or null if it should fit the text) (default: _null_)
   * @param $x The x offset of the text (or null if it should be centered) (default: _null_)
   * @param $y The y offset of the text (or null if the baseline should be the image border) (default: _null_)
   * @param $angle The angle of the text (optional, default: _0_)
   * @return Boolean whether the operation succeeded, error string provided by getErrorMsg()
   */
  public function renderText($text, $fontfile, $fontsize, $color, $bgcolor, $filename,
    $width=null, $height=null, $x=null, $y=null, $angle=0) {
    try {
      // the destination lines array
      $dstLines = array();
      $lineheight = $height;

      // if a width is given, we wrap the text if necessary
      if ($width != null) {
        // remove windows line-breaks
        $text = str_replace("\r", '', $text);
        // split text into "lines"
        $srcLines = split ("\n", $text);
        foreach ($srcLines as $currentL) {
          $line = '';
          // split line into words
          $wordsTmp = split(" ", $currentL);
          // split at hyphens
          $words = array();
          foreach ($wordsTmp as $word) {
            $wordParts = split(' ', str_replace(array('-', '/'), array('- ', '/ '), $word));
            foreach ($wordParts as $wordPart) {
              $words[] = $wordPart;
            }
          }
          for ($i=0, $count=sizeof($words); $i<$count; $i++) {
            $word = $words[$i];

            // get the length of this line, if the word is to be included
            list($linewidth, $lineheight) = $this->getTextDimension($fontsize, $angle, $fontfile, $text);

            // check if it is too big if the word was added, if so, then move on
            if ($linewidth > $width && !empty($line)) {
               // add the line like it was without spaces
              $dstLines[] = trim($line);
              $line = '';
            }
            // add the trailing space only if the word does not end with a hyphen
            // and it is not the last word
            if (preg_match('/-$/', $word) || $i==sizeof($words)-1) {
              $line .= $word;
            }
            else {
              $line .= $word.' ';
            }
          }
          // add the line when the line ends
          $dstLines[] = trim($line);
        }
        // get the text dimensions
        $textwidth = $width;
        if ($height != null) {
          $lineheight = $height;
        }
        $textheight = sizeof($dstLines)*$lineheight;
        $height = $textheight;
      }
      else {
        $dstLines[] = $text;
        // get the text dimensions
        list($textwidth, $textheight) = $this->getTextDimension($fontsize, $angle, $fontfile, $text);

        // add 5 pixels to the width.
        // @todo make this a parameter
        $textwidth += 5;

        // calculate offset and dimensions
        list($width, $height, $x, $y) = $this->calculateTextParams($width, $height, $x, $y, $textwidth, $textheight);
      }

      // create the image
      $image = ImageWorkshop::initVirginLayer($width, $height, $bgcolor);

      // render the text onto the image
      foreach ($dstLines as $nr => $line) {
        // calculate offset and dimensions
        list($width, $height, $x, $y) = $this->calculateTextParams($width, $height, $x, $y, $textwidth, $textheight);
        // print the line
        $image->write("$line", $fontfile, $fontsize, $color, $x, $y, $angle);
        $y += $lineheight;
      }

      // write the image
      $image->save(dirname($filename), basename($filename), true, null, 100);
      return true;
    } catch (\Exception $ex) {
      $this->errorMsg = $ex->getMessage();
      return false;
    }
  }

  /**
   * Calculate the dimension of the given text
   * @param $fontsize The font size (in pixels)
   * @param $angle The angle of the characters (optional, default: _0_)
   * @param $fontfile The font file
   * @param $text The text
   * @return An array with the width and height values
   */
  private function getTextDimension($fontsize, $angle, $fontfile, $text) {
    list($x2, $y2, $x3, $y3, $x1, $y1, $x0, $y0) = imagettfbbox($fontsize, $angle, $fontfile, $text);
    return array($x1-$x2, $y2-$y1);
  }

  /**
   * Calculate the offset of the text and the size of the image based on the given parameters
   * @param $width The width of the image (or null if it should be the textwidth)
   * @param $height The height of the image (or null if it should be the textheight)
   * @param $x The x offset of the text (or null if it should be centered)
   * @param $y The y offset of the text (or null if the baseline should be the image border)
   * @param $textwidth The width of the text
   * @param $textheight The height of the text
   * @return An array with width, height, x, y values
   */
  private function calculateTextParams($width, $height, $x, $y, $textwidth, $textheight) {
    // calculate dimensions
    if ($width === null) {
      $width = $textwidth;
    }
    if ($height === null) {
      $height = $textheight;
    }
    // calculate offset
    if ($x === null) {
      $x = ($width-$textwidth)/2;
    }
    if ($y === null) {
      $y = $height;
    }
    return array($width, $height, $x, $y);
  }

  /**
   * Calculate the size based on the image aspect, if only width or height
   * are given.
   * @param $width The requested width (maybe null)
   * @param $height The requested height (maybe null)
   * @param $imageWidth The image's width
   * @param $imageHeight The image's height
   * @return Array with width, height
   */
  private function calculateSizeParams($width, $height, $imageWidth, $imageHeight) {
    if ($width == null) {
      $width = $imageWidth/$imageHeight*$height;
    }
    elseif ($height == null) {
      $height = $imageHeight/$imageWidth*$width;
    }
    return array(intval($width), intval($height));
  }
}
?>
