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
namespace wcmf\lib\model\output;

use wcmf\lib\model\output\ImageOutputStrategy;
use wcmf\lib\model\output\IOutputStrategy;

/**
 * ElementImageOutputStrategy outputs a tree of objects into an image file.
 * It must be configured with a map that was calculated by a LayoutVisitor.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ElementImageOutputStrategy extends ImageOutputStrategy {

  /**
   * Constructor.
   * @param format Image format name [IMG_GIF | IMG_JPG | IMG_PNG | IMG_WBMP].
   * @param file The output file name.
   * @param map The position map provided by LayoutVisitor.
   * @param lineType The linetype to use [LINETYPE_DIRECT|LINETYPE_ROUTED] DEFAULT LINETYPE_DIRECT.
   * @param scale The image scale (will be xscale) DEFAULT 100.
   * @param aspect The image aspect (aspect = xscale/yscale) DEFAULT 0.5.
   * @param border The image border [px] DEFAULT 50.
   * @param usemap Name of the HTML ImageMap to write to stdout ['' means no map] DEFAULT ''.
   */
  public function __construct($format, $file, $map, $lineType=self::LINETYPE_DIRECT, $scale=100,
          $aspect=0.5, $border=50, $usemap='') {

    parent::__construct($format, $file, $map, $lineType, $scale, $aspect, $border, $usemap);
    // define label dimensions relative to node connector position
    $this->_labelDim['left']   = -10;
    $this->_labelDim['top']    = -10;
    $this->_labelDim['right']  = 80;
    $this->_labelDim['bottom'] = 45;
    // define text position relative to node connector position
    $this->_textPos['left']   = -5;
    $this->_textPos['top']    = -8;
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    parent::writeHeader();
    // print legend
    $color = ImageColorAllocate($this->_img,0,150,0);
    $this->writeFilledBorderedRect(new Position($this->_border, $this->_border, 0),
                                   new Position($this->_border+10, $this->_border+10, 0), $this->_bgColor, $color);
    ImageString($this->_img,1, $this->_border+20, $this->_border+2, "optional", $color);
    $color = $this->_txtColor;
    $this->writeFilledBorderedRect(new Position($this->_border, $this->_border+20, 0),
                                   new Position($this->_border+10, $this->_border+30, 0), $this->_bgColor, $color);
    ImageString($this->_img,1, $this->_border+20, $this->_border+22, "required", $color);
    $color = ImageColorAllocate($this->_img,150,150,150);
    for($i=2;$i>=0;$i--) {
      $this->writeFilledBorderedRect(new Position($this->_border+2*$i, $this->_border+40+2*$i, 0),
                                     new Position($this->_border+10+2*$i, $this->_border+50+2*$i, 0), $this->_bgColor, $color);
    }
    ImageString($this->_img,1, $this->_border+20, $this->_border+42, "repetitive", $color);
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject($obj) {
    $properties = $obj->getValueProperties($obj->getType());
    if (!strstr($obj->getType(), '->')) {
      $smallText = $obj->getType();
      $bigText = $properties['oid'];
      $color = $this->_txtColor;
    }
    else {
      $smallText = substr ($obj->getType(), 0, strrpos ($obj->getType(), ":"));
      $bigText = substr (strrchr ($obj->getType(), ":"), 1);
      $color = ImageColorAllocate($this->_img,150,150,150);
    }

    $oid = $obj->getOID();
    $x = $this->_map[$oid]->x * $this->_xscale - $this->_labelDim['left'] +  $this->_border;
    $y = $this->_map[$oid]->y * $this->_yscale - $this->_labelDim['top'] +  $this->_border;

    if ($obj->getProperty('minOccurs') == 0) { // optional
      $color = ImageColorAllocate($this->_img,0,150,0);
    }

    // print box
    if ($obj->getProperty('maxOccurs') == 'unbounded' || $obj->getProperty('maxOccurs') > 1) {
      for($i=3;$i>=1;$i--) {
        $this->writeFilledBorderedRect(new Position($x + $this->_labelDim['left']+5*$i, $y + $this->_labelDim['top']+5*$i, 0),
                                new Position($x + $this->_labelDim['right']+5*$i, $y + $this->_labelDim['bottom']+5*$i, 0),
                                $this->_bgColor, $color);
      }
    }
    // print label
    $this->writeFilledBorderedRect(new Position($x + $this->_labelDim['left'], $y + $this->_labelDim['top'], 0),
                            new Position($x + $this->_labelDim['right'], $y + $this->_labelDim['bottom'], 0),
                            $this->_bgColor, $color);
    // write text
    ImageString($this->_img,2,
                $x + $this->_textPos['left'],
                $y + $this->_textPos['top'],
                $smallText,
                $color);
    ImageString($this->_img,1,
                $x + $this->_textPos['left'],
                $y + $this->_textPos['top']+15,
                "E: ".$properties['data_type'],
                $color);
    // write attribs
    $attribs = $obj->getValueNames();
    $i = 0;
    if (is_array($attribs)) {
      foreach ($attribs as $attrib) {
        ImageString($this->_img,1,
              $x + $this->_textPos['left'],
              $y + $this->_textPos['top']+25+10*$i,
              "A: ".$attrib,
              $color);
        $i++;
      }
    }
    ImageString($this->_img,45,
                $x + $this->_textPos['left']+65,
                $y + $this->_textPos['top']+37,
                $bigText,
                $color);

    // draw line
    $parent = $obj->getParent();
    if ($parent) {
      $this->drawConnectionLine($parent->getOID(), $oid);
    }
    // print map
    if ($this->_usemap != '') {
      echo '<area shape="rect" coords="'.
        ($x + $this->_labelDim['left']).','.
        ($y + $this->_labelDim['top']).','.
        ($x + $this->_labelDim['right']).','.
        ($y + $this->_labelDim['bottom'] + 8*$this->_map[$oid]->z).
        '" onclick="javascript:alert(\'Node OID: '.$obj->getOID().'\')" alt="'.$obj->getOID().'">'."\n";
    }
  }

  private function writeFilledBorderedRect($topleft, $bottomright, $bgcolor, $bordercolor) {
    ImageFilledRectangle($this->_img, $topleft->x, $topleft->y, $bottomright->x, $bottomright->y, $bgcolor);
    ImageRectangle($this->_img, $topleft->x, $topleft->y, $bottomright->x, $bottomright->y, $bordercolor);
  }
}
?>

