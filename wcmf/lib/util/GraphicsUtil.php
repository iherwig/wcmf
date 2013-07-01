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
namespace wcmf\lib\util;

use \Exception;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\i18n\Message;
use wcmf\lib\util\GraphicsUtil;

/**
 * GraphicsUtil provides support for graphic manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class GraphicsUtil {

  const FONTTYPE_PS = 1;  // postscript type 1
  const FONTTYPE_TTF = 2; // true type

  /**
   * Check if a given file is an image.
   * @param imgname Name of the imagefile to check
   * @return True/False whether the file is an image
   */
  public static function isImage($imgname) {
    if(getimagesize($imgname)==false) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * Check image dimensions.
   * @param imgname Name of the imagefile to check
   * @param width Width of the image, -1 means don't care
   * @param height Height of the image, -1 means don't care
   * @param exact True/False whether the image should match the dimension exactly or might be smaller [default: true]
   * @return True/False whether the image meets the dimensions
   */
  public static function isValidImageDimension($imgname, $width, $height, $exact=true) {
    $widthOk = self::isValidImageWidth($imgname, $width, $exact);
    $heightOk = self::isValidImageHeight($imgname, $height, $exact);
    return ($widthOk && $heightOk);
  }

  /**
   * Check image width.
   * @param imgname Name of the imagefile to check
   * @param width Width of the image
   * @param exact True/False whether the image width should match exactly or might be smaller [default: true]
   * @return True/False whether the image width meets the criteria
   * @note This method returns true if the file does not exist.
   */
  public static function isValidImageWidth($imgname, $width, $exact=true) {
    if (!file_exists($imgname)) {
      return true;
    }
    $properties = getimagesize($imgname);
    $dimOk = true;
    if ($exact && $properties[0] != $width) {
      $dimOk = false;
    }
    else if ($properties[0] > $width) {
      $dimOk = false;
    }
    if (!$dimOk) {
      if ($exact) {
        $constraint = Message::get("exactly");
      }
      else {
        $constraint = Message::get("smaller than");
      }
      throw new Exception(Message::get("Wrong image width. Image width must be %0% %1%px - actual image width is %2%px.",
        array($constraint, $width, $properties[0])));
    }
    return $dimOk;
  }

  /**
   * Check image height.
   * @param imgname Name of the imagefile to check
   * @param height Height of the image
   * @param exact True/False whether the image height should match exactly or might be smaller [default: true]
   * @return True/False whether the image width meets the criteria
   * @note This method returns true if the file does not exist.
   */
  public static function isValidImageHeight($imgname, $height, $exact=true) {
    if (!file_exists($imgname)) {
      return true;
    }
    $properties = getimagesize($imgname);
    $dimOk = true;
    if ($exact && $properties[1] != $height) {
      $dimOk = false;
    }
    else if ($properties[1] > $height) {
      $dimOk = false;
    }
    if (!$dimOk) {
      if ($exact) {
        $constraint = Message::get("exactly");
      }
      else {
        $constraint = Message::get("smaller than");
      }
      throw new Exception(Message::get("Wrong image height. Image height must be %0% %1%px - actual image height is %2%px.",
        array($constraint, $height, $properties[1])));
    }
    return $dimOk;
  }

  /**
   * Create a thumbnail of an image file.
   * @param srcName The source file name
   * @param destName The destination file name
   * @param width The width of the thumbnail (maybe null)
   * @param height The height of the thumbnail (maybe null)
   * @return True/False whether the operation succeeded
   * @note: supported image formats are GIF, JPG, PNG
   *        if only width or height are given the other dimension is calculated to preserve the aspect
   */
  public static function createThumbnail($srcName, $destName, $width, $height) {
    self::checkImageSupport();

    $oldErrorLevel = error_reporting (0);
    $srcImg = null;
    $destImg = null;

    if ($width == null && $height == null) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Either width or height must be given."), false, $oldErrorLevel);
    }
    if ($width) $width = intval($width);
    if ($height) $height = intval($height);

    if ($width === 0 || $height === 0) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Dimension 0 is not allowed."), false, $oldErrorLevel);
    }
    // get image information to calculate aspect
    $srcData = getimagesize($srcName);
    if ($width == null) {
      $width = $srcData[0]/$srcData[1]*$height;
    }
    elseif ($height == null) {
      $height = $srcData[1]/$srcData[0]*$width;
    }
    // create thumbnail canvas to save to
    $destImg = imagecreatetruecolor($width, $height);
    if (!$destImg) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Could not create thumbnail image %0%.", array($destName)),
        false, $oldErrorLevel);
    }
    // define image functions
    $imagecreationFunction = self::getImageCreationFunction($srcName);
    if ($imagecreationFunction === false) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Image format not supported."), false, $oldErrorLevel);
    }
    $imageoutputFunction = self::getImageOutputFunction($destName);

    // create thumbnail
    $srcImg = $imagecreationFunction ($srcName);
    if (!$srcImg) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Could not open source image %0%.", array($srcName)),
        false, $oldErrorLevel);
    }
    imagecopyresampled ($destImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcData[0], $srcData[1]);
    // the last parameter gives the quality and will only be used with imagejpeg
    if ($imageoutputFunction == "imagejpeg") {
      $imageoutputFunction ($destImg, $destName, 100);
    }
    else {
      $imageoutputFunction ($destImg, $destName);
    }
    chmod($destName, 0644);

    return self::exitThumbnail($srcImg, $destImg, "", true, $oldErrorLevel);
  }

  /**
   */
  private static function exitThumbnail($srcImg, $destImg, $msg, $created, $oldErrorLevel) {
    // free resources
    if ($srcImg) imagedestroy($srcImg);
    if ($destImg) imagedestroy($destImg);

    error_reporting($oldErrorLevel);
    return $created;
  }

  /**
   * Crop an image to the given size starting from the middle of a given start point.
   * @param srcName The source file name
   * @param destName The destination file name
   * @param width The width of the cropped image (maybe null)
   * @param height The height of the cropped image (maybe null)
   * @param x The start point x coordinate (maybe null, default null)
   * @param y The start point y coordinate (maybe null, default null)
   * @return True/False whether the operation succeeded
   * @note: supported image formats are GIF, JPG, PNG
   *        if only width or height are given the other dimension is taken from the original image
   */
  public static function cropImage($srcName, $destName, $width, $height, $x=null, $y=null) {
    self::checkImageSupport();

    $oldErrorLevel = error_reporting (0);
    $srcImg = null;
    $destImg = null;

    if ($width == null && $height == null) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Either width or height must be given."), false, $oldErrorLevel);
    }
    if ($width) $width = intval($width);
    if ($height) $height = intval($height);

    if ($width === 0 || $height === 0) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Dimension 0 is not allowed."), false, $oldErrorLevel);
    }
    // get image information to calculate missing dimension
    $srcData = getimagesize($srcName);
    if ($width == null) {
      $width = $srcData[0];
    }
    elseif ($height == null) {
      $height = $srcData[1];
    }
    // create cropped image canvas to save to
    $destImg = imagecreatetruecolor($width, $height);
    if (!$destImg) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Could not create cropped image %0%.", array($destName)),
        false, $oldErrorLevel);
    }
    // define image functions
    $imagecreationFunction = GraphicsUtil::getImageCreationFunction($srcName);
    if ($imagecreationFunction === false) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Image format not supported."), false, $oldErrorLevel);
    }
    $imageoutputFunction = GraphicsUtil::getImageOutputFunction($destName);

    // create thumbnail
    $srcImg = $imagecreationFunction ($srcName);
    if (!$srcImg) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Could not open source image %0%.", array($srcName)),
        false, $oldErrorLevel);
    }
    if ($x === null)
      $x = $srcData[0]/2;
    if ($y === null)
      $y = $srcData[1]/2;
    imagecopyresampled ($destImg, $srcImg, 0, 0, $x, $y, $width, $height, $width, $height);
    // the last parameter gives the quality and will only be used with imagejpeg
    if ($imageoutputFunction == "imagejpeg") {
      $imageoutputFunction ($destImg, $destName, 100);
    }
    else {
      $imageoutputFunction ($destImg, $destName);
    }
    chmod($destName, 0644);

    return self::exitThumbnail($srcImg, $destImg, "", true, $oldErrorLevel);
  }

  /**
   * Create a black and white copy of an image.
   * Code is from http://php.about.com/od/gdlibrary/ss/grayscale_gd.htm
   * @param srcName The source file name
   * @param destName The destination file name
   */
  public static function createBlackWhiteImage($srcName, $destName) {
    // define image functions
    $imagecreationFunction = self::getImageCreationFunction($srcName);
    if ($imagecreationFunction === false) {
      return self::exitThumbnail($srcImg, $destImg, Message::get("Image format not supported."), false, $oldErrorLevel);
    }
    $imageoutputFunction = self::getImageOutputFunction($destName);

    // get the dimensions
    list($width, $height) = getimagesize($srcName);

    // define the source image
    $source = $imagecreationFunction($srcName);

    // create the canvas
    $bwimage= imagecreate($width, $height);

    // create the 256 color palette
    $palette = array(255);
    for ($c=0; $c<256; $c++) {
      $palette[$c] = imagecolorallocate($bwimage, $c, $c, $c);
    }

    // read the original colors pixel by pixel
    for ($y=0; $y<$height; $y++) {
      for ($x=0; $x<$width; $x++) {
        $rgb = imagecolorat($source, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        //This is where we actually use yiq to modify our rbg values, and then convert them to our grayscale palette
        $gs = ($r*0.299) + ($g*0.587) + ($b*0.114);
        imagesetpixel($bwimage, $x, $y, $palette[$gs]);
      }
    }

    // output a jpg image
    $imageoutputFunction($bwimage, $destName);

    // destroy temp image buffers
    imagedestroy($source);
    imagedestroy($bwimage);
  }

  /**
   * Render a text to an image. Using the default parameters the text will
   * be rendered into a box that fits the text. If the width parameter is not null and the
   * text exceeds the width, the text will be wrapped and the height parameter will be
   * used as lineheight.
   * @param text The text to render
   * @param fontfile The font file to use
   * @param encoding The encoding file to use (e.g. IsoLatin1.enc, maybe null for default encoding)
   * @param fontsize The font size to use (in pixels)
   * @param color The color to use for the text (as HEX value)
   * @param bgcolor The color to use for the background (as HEX value)
   * @param filename The name of the file to write to
   * @param width The width of the image (or null if it should fit the text) [default: null]
   * @param height The height of the image (or null if it should fit the text) [default: null]
   * @param x The x offset of the text (or null if it should be centered) [default: null]
   * @param y The y offset of the text (or null if the baseline should be the image border) [default: null]
   * @param space The value of the space character in the font (optional) [default: 0]
   * @param tightness The amount of white space between characters (optional) [default: 0]
   * @param angle The angle of the characters (optional) [default: 0]
   * @return True/False whether the operation succeeded
   * @note The image type will be determined by the file extension (gif, jpg or png). Default
   * is jpg if the extension is unknown.
   */
  public static function renderTextPS($text, $fontfile, $encoding, $fontsize, $color, $bgcolor, $filename,
    $width=null, $height=null, $x=null, $y=null, $space=0, $tightness=0, $angle=0) {

    self::checkImageSupport();

    // check postscript type1 support
    if (!function_exists(imagepsloadfont)) {
      throw new Exception('t1lib missing');
    }

    // load the font to use
    if (!file_exists($fontfile)) {
      throw new IllegalArgumentException(Message::get("Font file '%0%' not found.", array($fontfile)));
    }

    $font = @imagepsloadfont($fontfile);
    if ($font === false) {
      throw new IllegalArgumentException(Message::get("Format of font file '%0%' not supported.", array($fontfile)));
    }
    if ($encoding != null) {
      imagepsencodefont($font, $encoding);
    }
    // create the image file
    self::createTextImage(self::FONTTYPE_PS, $text, $font, null, $fontsize, $color, $bgcolor, $filename,
      $width, $height, $x, $y, $space, $tightness, $angle);

    // destroy font to free memory
    imagepsfreefont($font);
    return true;
  }

  /**
   * Render a text to an image. Using the default parameters the text will
   * be rendered into a box that fits the text. If the width parameter is not null and the
   * text exceeds the width, the text will be wrapped and the height parameter will be
   * used as lineheight.
   * @param text The text to render
   * @param fontfile The font file to use
   * @param fontsize The font size to use (in pixels)
   * @param color The color to use for the text (as HEX value)
   * @param bgcolor The color to use for the background (as HEX value)
   * @param filename The name of the file to write to
   * @param width The width of the image (or null if it should fit the text) [default: null]
   * @param height The height of the image (or null if it should fit the text) [default: null]
   * @param x The x offset of the text (or null if it should be centered) [default: null]
   * @param y The y offset of the text (or null if the baseline should be the image border) [default: null]
   * @param angle The angle of the text (optional) [default: 0]
   * @return True/False whether the operation succeeded
   * @note The image type will be determined by the file extension (gif, jpg or png). Default
   * is jpg if the extension is unknown.
   */
  public static function renderTextTTF($text, $fontfile, $fontsize, $color, $bgcolor, $filename,
    $width=null, $height=null, $x=null, $y=null, $angle=0) {

    self::checkImageSupport();

    // check postscript type1 support
    if (!function_exists(imagettfbbox)) {
      throw new Exception('FreeType library missing');
    }

    // load the font to use
    if (!file_exists($fontfile)) {
      throw new IllegalArgumentException(Message::get("Font file '%0%' not found.", array($fontfile)));
    }

    // create the image file
    self::createTextImage(self::FONTTYPE_TTF, $text, null, $fontfile, $fontsize, $color, $bgcolor, $filename,
      $width, $height, $x, $y, 0, 0, $angle);

    return true;
  }

  /**
   * Render a text to an image. This method is used by GraphicsUtil::renderTextPS
   * and GraphicsUtil::renderTextTTF.
   * Wrapping code is from http://de.php.net/manual/de/function.imagettfbbox.php#60673
   * @param fonttype One of the FONTTYPE constants
   * @param text The text
   * @param font The font identifier (ignored when using FONTYPE_TTF)
   * @param fontfile The font file (ignored when using FONTYPE_PS)
   * @param fontsize The font size (in pixels)
   * @param color The foreground color (as HEX value)
   * @param bgcolor The background color (as HEX value)
   * @param filename The name of the file to write to
   * @param width The width of the image (or null if it should fit the text)
   * @param height The height of the image (or null if it should fit the text)
   * @param x The x offset of the text (or null if it should be centered)
   * @param y The y offset of the text (or null if the baseline should be the image border)
   * @param space The value of the space character in the font
   * @param tightness The amount of white space between characters
   * @param angle The angle of the text
   */
  private static function createTextImage($fonttype, $text, $font, $fontfile, $fontsize, $color, $bgcolor, $filename,
    $width, $height, $x, $y, $space, $tightness, $angle) {
    // the destination lines array
    $dstLines = array();
    $lineheight = $height;

    // if a width is given, we wrap the text if necessary
    if ($width != null)
    {
      // remove windows line-breaks
      $text = str_replace("\r", '', $text);
      // split text into "lines"
      $srcLines = preg_split ('/\n/', $text);
      foreach ($srcLines as $currentL) {
        $line = '';
        // split line into words
        $wordsTmp = preg_split('/ /', $currentL);
        // split at hyphens
        $words = array();
        foreach ($wordsTmp as $word) {
          $wordParts = preg_split('/ /', str_replace(array('-', '/'), array('- ', '/ '), $word));
          foreach ($wordParts as $wordPart) {
            array_push($words, $wordPart);
          }
        }
        for ($i=0; $i<sizeof($words); $i++) {
          $word = $words[$i];

          // get the length of this line, if the word is to be included
          list($linewidth, $lineheight) = self::getTextDimension($fonttype, $line.$word,
            $font, $fontfile, $fontsize, $space, $tightness, $angle);

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
      list($textwidth, $textheight) = self::getTextDimension($fonttype, $text, $font, $fontfile,
        $fontsize, $space, $tightness, $angle);

      // add 5 pixels to the width.
      // @todo make this a parameter
      $textwidth += 5;

      // calculate offset and dimensions
      list($width, $height, $x, $y) = self::calculateTextParams($width, $height, $x, $y,
        $textwidth, $textheight);
    }

    // create the image
    list($im, $foreground, $background) = self::createImage($width, $height, $color, $bgcolor);

    // render the text onto the image
    foreach ($dstLines as $nr => $line) {
      // calculate offset and dimensions
      list($width, $height, $x, $y) = self::calculateTextParams($width, $height, $x, $y,
        $textwidth, $textheight);
      // print the line
      self::renderText($fonttype, $im, $line, $font, $fontfile, $fontsize, $foreground, $background,
        $x, $y, $space, $tightness, $angle);
      $y += $lineheight;
    }

    // write the image
    $imageoutputFunction = self::getImageOutputFunction($filename);
    $imageoutputFunction($im, $filename);
    chmod($filename, 0775);

    // destroy the image to free memory
    imagedestroy($im);
  }

  /**
   * Render text onto an image
   * @param fonttype One of the FONTTYPE constants
   * @param im The image to render onto
   * @param text The text
   * @param font The font identifier (ignored when using FONTYPE_TTF)
   * @param fontfile The font file (ignored when using FONTYPE_PS)
   * @param fontsize The font size (in pixels)
   * @param foreground The foreground color (provided by imagecolorallocate)
   * @param background The background color (provided by imagecolorallocate)
   * @param x The x position
   * @param y The y position
   * @param space The value of the space character in the font (optional, ignored when using FONTYPE_TTF) [default: null]
   * @param tightness The amount of white space between characters (optional, ignored when using FONTYPE_TTF) [default: null]
   * @param angle The angle of the characters (optional) [default: 0]
   */
  private static function renderText($fonttype, $im, $text, $font, $fontfile, $fontsize, $foreground, $background,
    $x, $y, $space, $tightness, $angle) {

    if ($fonttype == self::FONTTYPE_PS) {
      imagepstext($im, "$text", $font, $fontsize, $foreground, $background, $x, $y, $space, $tightness, $angle, 16);
    }
    elseif ($fonttype == self::FONTTYPE_TTF) {
      imagettftext($im, $fontsize, $angle, $x, $y, $foreground, $fontfile, "$text");
    }
    else {
      throw new IllegalArgumentException(Message::get('unknown FONTTYPE'));
    }
  }

  /**
   * Calculate the dimension of the given text
   * @param fonttype One of the FONTTYPE constants
   * @param text The text
   * @param font The font identifier (ignored when using FONTYPE_TTF)
   * @param fontfile The font file (ignored when using FONTYPE_PS)
   * @param fontsize The font size (in pixels)
   * @param space The value of the space character in the font (optional, ignored when using FONTYPE_TTF) [default: null]
   * @param tightness The amount of white space between characters (optional, ignored when using FONTYPE_TTF) [default: null]
   * @param angle The angle of the characters (optional) [default: 0]
   * @return An array with the width and height values
   */
  private static function getTextDimension($fonttype, $text, $font, $fontfile, $fontsize,
          $space=null, $tightness=null, $angle=0) {

    if ($fonttype == self::FONTTYPE_PS) {
      list($x0, $y0, $x3, $y3) = imagepsbbox($text , $font, $fontsize, $space, $tightness, $angle);
      return array($x3-$x0, $y3-$y0);
    }
    elseif ($fonttype == self::FONTTYPE_TTF) {
      list($x2, $y2, $x3, $y3, $x1, $y1, $x0, $y0) = imagettfbbox($fontsize, $angle, $fontfile, $text);
      return array($x1-$x2, $y2-$y1);
    }
    else
    {
      throw new IllegalArgumentException(Message::get('unknown FONTTYPE'));
    }
  }

  /**
   * Calculate the offset of the text and the size of the image based on the given parameters
   * @param width The width of the image (or null if it should be the textwidth)
   * @param height The height of the image (or null if it should be the textheight)
   * @param x The x offset of the text (or null if it should be centered)
   * @param y The y offset of the text (or null if the baseline should be the image border)
   * @param textwidth The width of the text
   * @param textheight The height of the text
   * @return An array with width, height, x, y values
   */
  private static function calculateTextParams($width, $height, $x, $y, $textwidth, $textheight) {
    // calculate dimensions
    if ($width === null) $width = $textwidth;
    if ($height === null) $height = $textheight;

    // calculate offset
    if ($x === null) $x = ($width-$textwidth)/2;
    if ($y === null) $y = $height;

    return array($width, $height, $x, $y);
  }

  /**
   * Create a true color image with given parameters
   * @param width The width of the image
   * @param height The height of the image
   * @param color The foreground color (as HEX value)
   * @param bgcolor The background color (as HEX value)
   * @return An array with the image object, the foreground and background color
   */
  private static function createImage($width, $height, $color, $bgcolor) {
    // create the image
    $im = imagecreatetruecolor($width, $height);

    // define the colors
    list($r, $g, $b) = self::HEX2RGB($color);
    $foreground = imagecolorallocate($im, $r, $g, $b);
    list($r, $g, $b) = self::HEX2RGB($bgcolor);
    $background = imagecolorallocate($im, $r, $g, $b);
    imagefill($im, 0, 0, $background);
    return array($im, $foreground, $background);
  }

  /**
   * Get the image output function depending on the filename to write
   * to (e.g. *.jpg -> imagejpeg)
   * @param filename The filename
   * @return The PHP function to use for output to the required image format. Default is imagejpeg.
   */
  private static function getImageOutputFunction($filename) {
    $extension = substr($filename, strrpos($filename, ".")+1);
    $imageoutputFunction = "imagejpeg";
    if (strtolower($extension) == 'gif') {
      $imageoutputFunction = "imagegif";
    }
    elseif (strtolower($extension) == 'jpg') {
      $imageoutputFunction = "imagejpeg";
    }
    elseif (strtolower($extension) == 'png') {
      $imageoutputFunction = "imagepng";
    }
    return $imageoutputFunction;
  }

  /**
   * Get the image creation function depending on the file to read from
   * @param filename The filename
   * @return The PHP function to use for create an image in the required image format
   * or false if the format is not supported.
   */
  private static function getImageCreationFunction($filename) {
    $srcData = getimagesize($filename);
    $type = $srcData[2];

    if ($type == 1 && imagetypes() & IMG_GIF) {
      $imagecreationFunction = "imagecreatefromgif";
    }
    elseif ($type == 2 && imagetypes() & IMG_JPG) {
      $imagecreationFunction = "imagecreatefromjpeg";
    }
    elseif ($type == 3 && imagetypes() & IMG_PNG) {
      $imagecreationFunction = "imagecreatefrompng";
    }
    else {
      $imagecreationFunction = false;
    }
    return $imagecreationFunction;
  }

  /**
   * Convert a HEX color value to an array containing the RGB components
   * @param hexColor The HEX color value
   * @return An array with Red, Green and Blue components
   */
  private static function HEX2RGB($hexColor) {
    $rgbValues = array();

    // Split the HEX color representation
    $hexColor = chunk_split($hexColor, 2, "");
    $rh = substr($hexColor, 0, 2);
    $gh = substr($hexColor, 2, 2);
    $bh = substr($hexColor, 4, 2);

    // Convert HEX values to DECIMAL
    $rgbValues[0] = hexdec("0x{$rh}");
    $rgbValues[1] = hexdec("0x{$gh}");
    $rgbValues[2] = hexdec("0x{$bh}");

    return $rgbValues;
  }

  /**
   * See if gd lib is installed. We assume the version is ok.
   * Throws an exception if not.
   */
  private static function checkImageSupport() {
    if (!function_exists(imagecreatetruecolor)) {
      throw new RuntimeException(Message::get('gd library missing'));
    }
  }
}
?>
