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

/**
 * Some constants describing font types
 */
define("FONTTYPE_PS", 1); // postscript type 1
define("FONTTYPE_TTF", 2); // true type

/**
 * @class GraphicsUtil
 * @ingroup Util
 * @brief GraphicsUtil provides support for graphic manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class GraphicsUtil
{
  var $_errorMsg = '';

  /**
   * Get last error message.
   * @return The error string
   */
  function getErrorMsg()
  {
    return $this->_errorMsg;
  }
  /**
   * Check if a given file is an image.
   * @param imgname Name of the imagefile to check
   * @return True/False whether the file is an image
   */
  function isImage($imgname)
  {
    if(getimagesize($imgname)==false)
      return false;
    else
      return true;
  }
  /**
   * Check image dimensions.
   * @param imgname Name of the imagefile to check
   * @param width Width of the image, -1 means don't care
   * @param height Height of the image, -1 means don't care
   * @param exact True/False whether the image should match the dimension exactly or might be smaller [default: true]
   * @return True/False whether the image meets the dimensions, error string provided by getErrorMsg()
   */
  function isValidImageDimension($imgname, $width, $height, $exact=true)
  {
    $widthOk = $this->isValidImageWidth($imgname, $width, $exact);
    $heightOk = $this->isValidImageHeight($imgname, $height, $exact);
    return ($widthOk && $heightOk);
  }
  /**
   * Check image width.
   * @param imgname Name of the imagefile to check
   * @param width Width of the image
   * @param exact True/False whether the image width should match exactly or might be smaller [default: true]
   * @return True/False whether the image width meets the criteria, error string provided by getErrorMsg()
   * @note This method returns true if the file does not exist.
   */
  function isValidImageWidth($imgname, $width, $exact=true)
  {
    if (!file_exists($imgname))
      return true;

    $properties = getimagesize($imgname);
    $dimOk = true;
    if ($exact && $properties[0] != $width)
      $dimOk = false;
    else if ($properties[0] > $width)
      $dimOk = false;
    if (!$dimOk)
    {
      if ($exact)
        $constraint = Message::get("exactly");
      else
        $constraint = Message::get("smaller than");
      $this->_errorMsg .= Message::get("Wrong image width. Image width must be %1% %2%px - actual image width is %3%px.",
        array($constraint, $width, $properties[0]));
      $this->_errorMsg .= "\n";
    }
    return $dimOk;
  }
  /**
   * Check image height.
   * @param imgname Name of the imagefile to check
   * @param height Height of the image
   * @param exact True/False whether the image height should match exactly or might be smaller [default: true]
   * @return True/False whether the image width meets the criteria, error string provided by getErrorMsg()
   * @note This method returns true if the file does not exist.
   */
  function isValidImageHeight($imgname, $height, $exact=true)
  {
    if (!file_exists($imgname))
      return true;

    $properties = getimagesize($imgname);
    $dimOk = true;
    if ($exact && $properties[1] != $height)
      $dimOk = false;
    else if ($properties[1] > $height)
      $dimOk = false;
    if (!$dimOk)
    {
      if ($exact)
        $constraint = Message::get("exactly");
      else
        $constraint = Message::get("smaller than");
      $this->_errorMsg .= Message::get("Wrong image height. Image height must be %1% %2%px - actual image height is %3%px.",
        array($constraint, $height, $properties[1]));
      $this->_errorMsg .= "\n";
    }
    return $dimOk;
  }
  /**
   * Create a thumbnail of an image file.
   * @param srcName The source file name
   * @param destName The destination file name
   * @param width The width of the thumbnail (maybe null)
   * @param height The height of the thumbnail (maybe null)
   * @return True/False whether the operation succeeded, error string provided by getErrorMsg()
   * @note: supported image formats are GIF, JPG, PNG
   *        if only width or height are given the other dimension is calculated to preserve the aspect
   */
  function createThumbnail($srcName, $destName, $width, $height)
  {
    if (!$this->checkImageSupport())
      return false;

    $this->_errorMsg = '';
    $oldErrorLevel = error_reporting (0);
    $srcImg = null;
    $destImg = null;

    if ($width == null && $height == null)
      return $this->exitThumbnail($srcImg, $destImg, Message::get("Either width or height must be given."), false, $oldErrorLevel);

    if ($width) $width = intval($width);
    if ($height) $height = intval($height);

    if ($width === 0 || $height === 0)
      return $this->exitThumbnail($srcImg, $destImg, Message::get("Dimension 0 is not allowed."), false, $oldErrorLevel);

    // get image information to calculate aspect
    $srcData = getimagesize($srcName);
    if ($width == null)
      $width = $srcData[0]/$srcData[1]*$height;
    elseif ($height == null)
      $height = $srcData[1]/$srcData[0]*$width;

    // create thumbnail canvas to save to
    $destImg = imagecreatetruecolor($width, $height);
    if (!$destImg)
      return $this->exitThumbnail($srcImg, $destImg, Message::get("Could not create thumbnail image %1%.", array($destName)),
        false, $oldErrorLevel);

    // define image functions
    $imagecreationFunction = GraphicsUtil::getImageCreationFunction($srcName);
    if ($imagecreationFunction === false)
      return $this->exitThumbnail($srcImg, $destImg, Message::get("Image format not supported."), false, $oldErrorLevel);
    $imageoutputFunction = GraphicsUtil::getImageOutputFunction($destName);

    // create thumbnail
    $srcImg = $imagecreationFunction ($srcName);
    if (!$srcImg)
      return $this->exitThumbnail($srcImg, $destImg, Message::get("Could not open source image %1%.", array($srcName)),
        false, $oldErrorLevel);

    imagecopyresampled ($destImg, $srcImg, 0, 0, 0, 0, $width, $height, $srcData[0], $srcData[1]);
    // the last parameter gives the quality and will only be used with imagejpeg
    if ($imageoutputFunction == "imagejpeg")
      $imageoutputFunction ($destImg, $destName, 100);
    else
      $imageoutputFunction ($destImg, $destName);
    chmod($destName, 0644);

    return $this->exitThumbnail($srcImg, $destImg, "", true, $oldErrorLevel);
    }
  /**
   * @note: internal use only
   */
  function exitThumbnail($srcImg, $destImg, $msg, $created, $oldErrorLevel)
    {
    // free resources
    if ($srcImg) imagedestroy($srcImg);
    if ($destImg) imagedestroy($destImg);

    $this->_errorMsg = $msg;
    error_reporting ($oldErrorLevel);
    return $created;
  }
  /**
   * Create a black and white copy of an image.
   * Code is from http://php.about.com/od/gdlibrary/ss/grayscale_gd.htm
   * @param srcName The source file name
   * @param destName The destination file name
   */
  function createBlackWhiteImage($srcName, $destName)
  {
    // define image functions
    $imagecreationFunction = GraphicsUtil::getImageCreationFunction($srcName);
    if ($imagecreationFunction === false)
      return $this->exitThumbnail($srcImg, $destImg, Message::get("Image format not supported."), false, $oldErrorLevel);
    $imageoutputFunction = GraphicsUtil::getImageOutputFunction($destName);

    // get the dimensions
    list($width, $height) = getimagesize($srcName);

    // define the source image
    $source = $imagecreationFunction($srcName);

    // create the canvas
    $bwimage= imagecreate($width, $height);

    // create the 256 color palette
    $palette = array(255);
    for ($c=0; $c<256; $c++)
    {
      $palette[$c] = imagecolorallocate($bwimage, $c, $c, $c);
    }

    // read the original colors pixel by pixel
    for ($y=0; $y<$height; $y++)
    {
      for ($x=0; $x<$width; $x++)
      {
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
   * @return True/False whether the operation succeeded, error string provided by getErrorMsg()
   * @note The image type will be determined by the file extension (gif, jpg or png). Default
   * is jpg if the extension is unknown.
   */
  function renderTextPS($text, $fontfile, $encoding, $fontsize, $color, $bgcolor, $filename,
    $width=null, $height=null, $x=null, $y=null, $space=0, $tightness=0, $angle=0)
  {
    if (!$this->checkImageSupport())
      return false;

    // check postscript type1 support
    if (!function_exists(imagepsloadfont))
    {
      $this->_errorMsg = 't1lib missing';
      return false;
    }

    // load the font to use
    if (!file_exists($fontfile))
    {
      $this->_errorMsg .= Message::get("Font file '%1%' not found.", array($fontfile));
      return false;
    }

    $font = @imagepsloadfont($fontfile);
    if ($font === false)
    {
      $this->_errorMsg .= Message::get("Format of font file '%1%' not supported.", array($fontfile));
      return false;
    }
    if ($encoding != null)
      imagepsencodefont($font, $encoding);

    // create the image file
    GraphicsUtil::createTextImage(FONTTYPE_PS, $text, $font, null, $fontsize, $color, $bgcolor, $filename,
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
   * @return True/False whether the operation succeeded, error string provided by getErrorMsg()
   * @note The image type will be determined by the file extension (gif, jpg or png). Default
   * is jpg if the extension is unknown.
   */
  function renderTextTTF($text, $fontfile, $fontsize, $color, $bgcolor, $filename,
    $width=null, $height=null, $x=null, $y=null, $angle=0)
  {
    if (!$this->checkImageSupport())
      return false;

    // check postscript type1 support
    if (!function_exists(imagettfbbox))
    {
      $this->_errorMsg = 'FreeType library missing';
      return false;
    }

    // load the font to use
    if (!file_exists($fontfile))
    {
      $this->_errorMsg .= Message::get("Font file '%1%' not found.", array($fontfile));
      return false;
    }

    // create the image file
    GraphicsUtil::createTextImage(FONTTYPE_TTF, $text, null, $fontfile, $fontsize, $color, $bgcolor, $filename,
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
  function createTextImage($fonttype, $text, $font, $fontfile, $fontsize, $color, $bgcolor, $filename,
    $width, $height, $x, $y, $space, $tightness, $angle)
  {
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
      foreach ($srcLines as $currentL)
      {
        $line = '';
        // split line into words
        $wordsTmp = preg_split('/ /', $currentL);
        // split at hyphens
        $words = array();
        foreach ($wordsTmp as $word)
        {
          $wordParts = preg_split('/ /', str_replace(array('-', '/'), array('- ', '/ '), $word));
          foreach ($wordParts as $wordPart)
            array_push($words, $wordPart);
        }
        for ($i=0; $i<sizeof($words); $i++)
        {
          $word = $words[$i];

          // get the length of this line, if the word is to be included
          list($linewidth, $lineheight) = GraphicsUtil::getTextDimension($fonttype, $line.$word,
            $font, $fontfile, $fontsize, $space, $tightness, $angle);

          // check if it is too big if the word was added, if so, then move on
          if ($linewidth > $width && !empty($line))
          {
             // add the line like it was without spaces
            $dstLines[] = trim($line);
            $line = '';
          }
          // add the trailing space only if the word does not end with a hyphen
          // and it is not the last word
          if (preg_match('/-$/', $word) || $i==sizeof($words)-1)
            $line .= $word;
          else
            $line .= $word.' ';
        }
        // add the line when the line ends
        $dstLines[] = trim($line);
      }
      // get the text dimensions
      $textwidth = $width;
      if ($height != null)
        $lineheight = $height;
      $textheight = sizeof($dstLines)*$lineheight;
      $height = $textheight;
    }
    else
    {
      $dstLines[] = $text;
      // get the text dimensions
      list($textwidth, $textheight) = GraphicsUtil::getTextDimension($fonttype, $text, $font, $fontfile,
        $fontsize, $space, $tightness, $angle);

      // add 5 pixels to the width.
      // @todo make this a parameter
      $textwidth += 5;

      // calculate offset and dimensions
      list($width, $height, $x, $y) = GraphicsUtil::calculateTextParams($width, $height, $x, $y,
        $textwidth, $textheight);
    }

    // create the image
    list($im, $foreground, $background) = GraphicsUtil::createImage($width, $height, $color, $bgcolor);

    // render the text onto the image
    foreach ($dstLines as $nr => $line)
    {
      // calculate offset and dimensions
      list($width, $height, $x, $y) = GraphicsUtil::calculateTextParams($width, $height, $x, $y,
        $textwidth, $textheight);
      // print the line
      GraphicsUtil::renderText($fonttype, $im, $line, $font, $fontfile, $fontsize, $foreground, $background,
        $x, $y, $space, $tightness, $angle);
      $y += $lineheight;
    }

    // write the image
    $imageoutputFunction = GraphicsUtil::getImageOutputFunction($filename);
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
  function renderText($fonttype, $im, $text, $font, $fontfile, $fontsize, $foreground, $background,
    $x, $y, $space, $tightness, $angle)
  {
    if ($fonttype == FONTTYPE_PS)
      imagepstext($im, "$text", $font, $fontsize, $foreground, $background, $x, $y, $space, $tightness, $angle, 16);
    elseif ($fonttype == FONTTYPE_TTF)
      imagettftext($im, $fontsize, $angle, $x, $y, $foreground, $fontfile, "$text");
    else
      $this->_errorMsg = Message::get('unknown FONTTYPE');
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
   * @return An array with the width and height values or false if the fonttype is unknown
   */
  function getTextDimension($fonttype, $text, $font, $fontfile, $fontsize, $space=null, $tightness=null, $angle=0)
  {
    if ($fonttype == FONTTYPE_PS)
    {
      list($x0, $y0, $x3, $y3) = imagepsbbox($text , $font, $fontsize, $space, $tightness, $angle);
      return array($x3-$x0, $y3-$y0);
    }
    elseif ($fonttype == FONTTYPE_TTF)
    {
      list($x2, $y2, $x3, $y3, $x1, $y1, $x0, $y0) = imagettfbbox($fontsize, $angle, $fontfile, $text);
      return array($x1-$x2, $y2-$y1);
    }
    else
    {
      $this->_errorMsg = Message::get('unknown FONTTYPE');
      return false;
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
  function calculateTextParams($width, $height, $x, $y, $textwidth, $textheight)
  {
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
  function createImage($width, $height, $color, $bgcolor)
  {
    // create the image
    $im = imagecreatetruecolor($width, $height);

    // define the colors
    list($r, $g, $b) = GraphicsUtil::HEX2RGB($color);
    $foreground = imagecolorallocate($im, $r, $g, $b);
    list($r, $g, $b) = GraphicsUtil::HEX2RGB($bgcolor);
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
  function getImageOutputFunction($filename)
  {
    $extension = substr($filename, strrpos($filename, ".")+1);
    $imageoutputFunction = "imagejpeg";
    if (strtolower($extension) == 'gif')
      $imageoutputFunction = "imagegif";
    else if (strtolower($extension) == 'jpg')
      $imageoutputFunction = "imagejpeg";
    else if (strtolower($extension) == 'png')
      $imageoutputFunction = "imagepng";
    return $imageoutputFunction;
  }

  /**
   * Get the image creation function depending on the file to read from
   * @param filename The filename
   * @return The PHP function to use for create an image in the required image format
   * or false if the format is not supported.
   */
  function getImageCreationFunction($filename)
  {
    $srcData = getimagesize($filename);
    $type = $srcData[2];

    if ($type == 1 && imagetypes() & IMG_GIF)
      $imagecreationFunction = "imagecreatefromgif";
    elseif ($type == 2 && imagetypes() & IMG_JPG)
      $imagecreationFunction = "imagecreatefromjpeg";
    elseif ($type == 3 && imagetypes() & IMG_PNG)
      $imagecreationFunction = "imagecreatefrompng";
    else
      $imagecreationFunction = false;
    return $imagecreationFunction;
  }

  /**
   * Convert a HEX color value to an array containing the RGB components
   * @param hexColor The HEX color value
   * @return An array with Red, Green and Blue components
   */
  function HEX2RGB($hexColor)
  {
    $rgbValues = array();

    // Split the HEX color representation
    $hexValues = chunk_split($hexColor, 2, "");
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
   * @return True/False wether the library is installed.
   */
  function checkImageSupport()
  {
    if (!function_exists(imagecreatetruecolor))
    {
      $this->_errorMsg = Message::get('gd library missing');
      return false;
    }
    return true;
  }
}
?>
