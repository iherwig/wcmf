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

use wcmf\lib\model\output\IOutputStrategy;

/**
 * ImageOutputStrategy outputs a tree of objects into an image file. It must be configured
 * with a map that was calculated by a LayoutVisitor.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ImageOutputStrategy implements IOutputStrategy {

  const LINETYPE_DIRECT = 0;
  const LINETYPE_ROUTED = 0;

  protected $_format = null;
  protected $_file = '';
  protected $_map = null;
  protected $_img = null;
  protected $_width = 0;
  protected $_height = 0;
  protected $_xscale = 0;
  protected $_yscale = 0;
  protected $_border = 0;
  protected $_bgColor = null;
  protected $_txtColor = null;
  protected $_lineColor = null;
  protected $_labelDim = null;
  protected $_textPos = null;
  protected $_usemap = '';

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
  public function __construct($format, $file, $map, $lineType=self::LINETYPE_DIRECT,
          $scale=100, $aspect=0.5, $border=50, $usemap='') {

    if (!(ImageTypes() & $format)) {
      IllegalArgumentException($format." image support is disabled.");
    }
    if (!is_array($map)) {
      IllegalArgumentException("Parameter map is no array.");
    }
    $this->_format = $format;
    $this->_file = $file;
    $this->_map = $map;
    $this->_lineType = $lineType;
    $this->_xscale = $scale;
    $this->_yscale = $scale/$aspect;
    $this->_border = $border;
    $this->_usemap = $usemap;
    // define label dimensions relative to connector position
    $this->_labelDim['left']   = -10;
    $this->_labelDim['top']    = -10;
    $this->_labelDim['right']  = 80;
    $this->_labelDim['bottom'] = 20;
    // define text position relative to connector position
    $this->_textPos['left']   = -5;
    $this->_textPos['top']    = -8;
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    // calculate bounding box
    while (list ($key, $val) = each ($this->_map)) {
      if($val->x >= $this->_width) {
        $this->_width = $val->x;
      }
      if($val->y >= $this->_height) {
        $this->_height = $val->y;
      }
    }
    $this->_width = $this->_width * $this->_xscale + $this->_labelDim['right'] - $this->_labelDim['left'] + 2*$this->_border;
    $this->_height = $this->_height * $this->_yscale + $this->_labelDim['bottom'] - $this->_labelDim['top'] + 2*$this->_border;
    $this->_img = ImageCreate($this->_width,$this->_height);
    $this->_bgColor = ImageColorAllocate($this->_img,255,255,255);
    $this->_txtColor = ImageColorAllocate($this->_img,0,128,192);
    $this->_lineColor = $this->_txtColor;
    ImageFilledRectangle($this->_img,0,0,$this->_width,$this->_height,$this->_bgColor);

    if ($this->_usemap != '') {
      echo "\n".'<map name="'.$this->_usemap.'">'."\n";
    }
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    ImageString($this->_img,1,$this->_width-350,$this->_height-10,'wemove digital solutions. '.date ("l dS of F Y h:i:s A"),$this->_txtColor);
    if ($this->_format & IMG_GIF) {
      ImageGIF($this->_img, $this->_file);
    }
    if ($this->_format & IMG_PNG) {
      ImagePNG($this->_img, $this->_file);
    }
    if ($this->_format & IMG_JPEG) {
      ImageJPEG($this->_img, $this->_file);
    }
    if ($this->_format & IMG_WBMP) {
      ImageWBMP($this->_img, $this->_file);
    }
    if ($this->_usemap != '') {
      echo "\n".'</map>'."\n";
    }
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject($obj) {
    $oid = $obj->getOID();
    $x = $this->_map[$oid]->x * $this->_xscale - $this->_labelDim['left'] +  $this->_border;
    $y = $this->_map[$oid]->y * $this->_yscale - $this->_labelDim['top'] +  $this->_border;

    $statusStr = '';
    if ($obj->getState() == PersistentObject::STATE_DIRTY) {
      $statusStr = 'M';
    }
    if ($obj->getState() == PersistentObject::STATE_NEW) {
      $statusStr = 'N';
    }
    if ($obj->getState() == PersistentObject::STATE_DELETED) {
      $statusStr = 'D';
    }

    // print label
    ImageRectangle($this->_img,
      $x + $this->_labelDim['left'],
      $y + $this->_labelDim['top'],
      $x + $this->_labelDim['right'],
      $y + $this->_labelDim['bottom'],
      $this->_txtColor);
    // write text
    ImageString($this->_img,1,
      $x + $this->_textPos['left'],
      $y + $this->_textPos['top'],
      $obj->getType(),
      $this->_txtColor);
    if (strlen($oid) > 7) {
      $idStr = "...".subStr($oid, strlen($oid)-4, 4);
    }
    else {
      $idStr = $oid;
    }
    ImageString($this->_img,5,
      $x + $this->_textPos['left'],
      $y + $this->_textPos['top']+14,
      $idStr.' '.$statusStr,
      $this->_txtColor);

    // draw line
    $parent = $obj->getParent();
    if ($parent) {
      $this->drawConnectionLine($parent->getOID(), $oid);
    }
    // print map
    if ($this->_usemap != '')
    {
      echo '<area shape="rect" coords="'.
        ($x + $this->_labelDim['left']).','.
        ($y + $this->_labelDim['top']).','.
        ($x + $this->_labelDim['right']).','.
        ($y + $this->_labelDim['bottom'] + 8*$this->_map[$oid]->z).
        '" onclick="javascript:if (nodeClicked) nodeClicked(\''.$obj->getOID().'\')" alt="'.$obj->getOID().'">'."\n";
    }
  }

  /**
   * Draw connection line.
   * @attention Internal use only.
   * @param poid The parent object's object id.
   * @param oid The object's object id.
   */
  protected function drawConnectionLine($poid, $oid) {
    list($start, $end) = $this->calculateEndPoints($poid, $oid);
    if($this->_lineType == self::LINETYPE_DIRECT) {
      $this->drawDirectLine($start, $end);
    }
    else if($this->_lineType == self::LINETYPE_ROUTED) {
      $this->drawRoutedLine($start, $end);
    }
  }

  /**
   * Draw direct line.
   * @attention Internal use only.
   * @param start The start point (Position).
   * @param end The end point (Position).
   */
  protected function drawDirectLine($start, $end) {
    ImageLine($this->_img,
      $start->x,
      $start->y,
      $end->x,
      $end->y,
      $this->_lineColor);
  }

  /**
   * Draw routed line.
   * @attention Internal use only.
   * @param start The start point (Position).
   * @param end The end point (Position).
   */
  protected function drawRoutedLine($start, $end) {
    if ($this->_map["type"] == MAPTYPE_HORIZONTAL) {
      ImageLine($this->_img,
        $start->x,
        $start->y,
        $start->x,
        $start->y-($start->y-$end->y)/2,
        $this->_lineColor);
      ImageLine($this->_img,
        $start->x,
        $start->y-($start->y-$end->y)/2,
        $end->x,
        $start->y-($start->y-$end->y)/2,
        $this->_lineColor);
      ImageLine($this->_img,
        $end->x,
        $start->y-($start->y-$end->y)/2,
        $end->x,
        $end->y,
        $this->_lineColor);
    }
    else {
      ImageLine($this->_img,
        $start->x,
        $start->y,
        $start->x+($end->x-$start->x)/2,
        $start->y,
        $this->_lineColor);
      ImageLine($this->_img,
        $start->x+($end->x-$start->x)/2,
        $start->y,
        $start->x+($end->x-$start->x)/2,
        $end->y,
        $this->_lineColor);
      ImageLine($this->_img,
        $start->x+($end->x-$start->x)/2,
        $end->y,
        $end->x,
        $end->y,
        $this->_lineColor);
    }
  }

  /**
   * Calculate line end points.
   * @attention Internal use only.
   * @param poid The parent object's object id.
   * @param oid The object's object id.
   * @return Array containing start and end position
   */
  private function calculateEndPoints($poid, $oid) {
    // from child...
    if ($this->_map["type"] == MAPTYPE_HORIZONTAL) {
      // connect from mid top...
      $x1 = $this->_map[$oid]->x * $this->_xscale + ($this->_labelDim['right'] - $this->_labelDim['left'])/2 + $this->_border;
      $y1 = $this->_map[$oid]->y * $this->_yscale + $this->_border - 1;
    }
    else {
      // connect from mid left...
      $x1 = $this->_map[$oid]->x * $this->_xscale + $this->_border - 1;
      $y1 = $this->_map[$oid]->y * $this->_yscale + ($this->_labelDim['bottom'] - $this->_labelDim['top'])/2 + $this->_border;
    }
    // ...to parent
    if ($this->_map["type"] == MAPTYPE_HORIZONTAL) {
      // ...to mid bottom
      $x2 = $this->_map[$poid]->x * $this->_xscale + ($this->_labelDim['right'] - $this->_labelDim['left'])/2 +  $this->_border;
      $y2 = $this->_map[$poid]->y * $this->_yscale + ($this->_labelDim['bottom'] - $this->_labelDim['top']) +  $this->_border + 1;
    }
    else {
      // ...to mid right
      $x2 = $this->_map[$poid]->x * $this->_xscale + $this->_labelDim['right'] - $this->_labelDim['left'] +  $this->_border + 1;
      $y2 = $this->_map[$poid]->y * $this->_yscale + ($this->_labelDim['bottom'] - $this->_labelDim['top'])/2 +  $this->_border;
    }
    return array(new Position($x1,$y1,0), new Position($x2,$y2,0));
  }
}
?>
