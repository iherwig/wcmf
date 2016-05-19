<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\output;

use wcmf\lib\model\output\Position;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PersistentObject;

/**
 * ImageOutputStrategy outputs a tree of objects into an image file. It must be configured
 * with a map that was calculated by a LayoutVisitor.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ImageOutputStrategy implements OutputStrategy {

  const LINETYPE_DIRECT = 0;
  const LINETYPE_ROUTED = 1;

  protected $format = null;
  protected $file = '';
  protected $map = null;
  protected $img = null;
  protected $width = 0;
  protected $height = 0;
  protected $xscale = 0;
  protected $yscale = 0;
  protected $border = 0;
  protected $bgColor = null;
  protected $txtColor = null;
  protected $lineColor = null;
  protected $lineType = null;
  protected $labelDim = null;
  protected $textPos = null;
  protected $usemap = '';

  /**
   * Constructor.
   * @param $format Image format name [IMG_GIF | IMG_JPG | IMG_PNG | IMG_WBMP].
   * @param $file The output file name.
   * @param $map The position map provided by LayoutVisitor.
   * @param $lineType The linetype to use [LINETYPE_DIRECT|LINETYPE_ROUTED] DEFAULT LINETYPE_DIRECT.
   * @param $scale The image scale (will be xscale) DEFAULT 100.
   * @param $aspect The image aspect (aspect = xscale/yscale) DEFAULT 0.5.
   * @param $border The image border [px] DEFAULT 50.
   * @param $usemap Name of the HTML ImageMap to write to stdout ['' means no map] DEFAULT ''.
   */
  public function __construct($format, $file, $map, $lineType=self::LINETYPE_DIRECT,
          $scale=100, $aspect=0.5, $border=50, $usemap='') {

    if (!(ImageTypes() & $format)) {
      IllegalArgumentException($format." image support is disabled.");
    }
    if (!is_array($map)) {
      IllegalArgumentException("Parameter map is no array.");
    }
    $this->format = $format;
    $this->file = $file;
    $this->map = $map;
    $this->lineType = $lineType;
    $this->xscale = $scale;
    $this->yscale = $scale/$aspect;
    $this->border = $border;
    $this->usemap = $usemap;
    // define label dimensions relative to connector position
    $this->labelDim['left'] = -10;
    $this->labelDim['top'] = -10;
    $this->labelDim['right'] = 80;
    $this->labelDim['bottom'] = 20;
    // define text position relative to connector position
    $this->textPos['left'] = -5;
    $this->textPos['top'] = -8;
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    // calculate bounding box
    while (list ($key, $val) = each ($this->map)) {
      if($val->x >= $this->width) {
        $this->width = $val->x;
      }
      if($val->y >= $this->height) {
        $this->height = $val->y;
      }
    }
    $this->width = $this->width * $this->xscale + $this->labelDim['right'] - $this->labelDim['left'] + 2*$this->border;
    $this->height = $this->height * $this->yscale + $this->labelDim['bottom'] - $this->labelDim['top'] + 2*$this->border;
    $this->img = ImageCreate($this->width,$this->height);
    $this->bgColor = ImageColorAllocate($this->img, 255, 255, 255);
    $this->txtColor = ImageColorAllocate($this->img, 0, 128, 192);
    $this->lineColor = $this->txtColor;
    ImageFilledRectangle($this->img, 0, 0, $this->width,$this->height,$this->bgColor);

    if ($this->usemap != '') {
      echo "\n".'<map name="'.$this->usemap.'">'."\n";
    }
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    ImageString($this->img, 1, $this->width-350, $this->height-10, 'wemove digital solutions. '.date ("l dS of F Y h:i:s A"), $this->txtColor);
    if ($this->format & IMG_GIF) {
      ImageGIF($this->img, $this->file);
    }
    if ($this->format & IMG_PNG) {
      ImagePNG($this->img, $this->file);
    }
    if ($this->format & IMG_JPEG) {
      ImageJPEG($this->img, $this->file);
    }
    if ($this->format & IMG_WBMP) {
      ImageWBMP($this->img, $this->file);
    }
    if ($this->usemap != '') {
      echo "\n".'</map>'."\n";
    }
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    $oid = $obj->getOID();
    $x = $this->map[$oid]->x * $this->xscale - $this->labelDim['left'] + $this->border;
    $y = $this->map[$oid]->y * $this->yscale - $this->labelDim['top'] + $this->border;

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
    ImageRectangle($this->img,
      $x + $this->labelDim['left'],
      $y + $this->labelDim['top'],
      $x + $this->labelDim['right'],
      $y + $this->labelDim['bottom'],
      $this->txtColor);
    // write text
    ImageString($this->img, 1,
      $x + $this->textPos['left'],
      $y + $this->textPos['top'],
      $obj->getType(),
      $this->txtColor);
    if (strlen($oid) > 7) {
      $idStr = "...".subStr($oid, strlen($oid)-4, 4);
    }
    else {
      $idStr = $oid;
    }
    ImageString($this->img, 5,
      $x + $this->textPos['left'],
      $y + $this->textPos['top']+14,
      $idStr.' '.$statusStr,
      $this->txtColor);

    // draw line
    $parent = $obj->getParent();
    if ($parent) {
      $this->drawConnectionLine($parent->getOID(), $oid);
    }
    // print map
    if ($this->usemap != '')
    {
      echo '<area shape="rect" coords="'.
        ($x + $this->labelDim['left']).','.
        ($y + $this->labelDim['top']).','.
        ($x + $this->labelDim['right']).','.
        ($y + $this->labelDim['bottom'] + 8*$this->map[$oid]->z).
        '" onclick="javascript:if (nodeClicked) nodeClicked(\''.$obj->getOID().'\')" alt="'.$obj->getOID().'">'."\n";
    }
  }

  /**
   * Draw connection line.
   * @param $poid The parent object's object id.
   * @param $oid The object's object id.
   */
  protected function drawConnectionLine($poid, $oid) {
    list($start, $end) = $this->calculateEndPoints($poid, $oid);
    if($this->lineType == self::LINETYPE_DIRECT) {
      $this->drawDirectLine($start, $end);
    }
    else if($this->lineType == self::LINETYPE_ROUTED) {
      $this->drawRoutedLine($start, $end);
    }
  }

  /**
   * Draw direct line.
   * @param $start The start point (Position).
   * @param $end The end point (Position).
   */
  protected function drawDirectLine($start, $end) {
    ImageLine($this->img,
      $start->x,
      $start->y,
      $end->x,
      $end->y,
      $this->lineColor);
  }

  /**
   * Draw routed line.
   * @param $start The start point (Position).
   * @param $end The end point (Position).
   */
  protected function drawRoutedLine($start, $end) {
    if ($this->map["type"] == MAPTYPE_HORIZONTAL) {
      ImageLine($this->img,
        $start->x,
        $start->y,
        $start->x,
        $start->y-($start->y-$end->y)/2,
        $this->lineColor);
      ImageLine($this->img,
        $start->x,
        $start->y-($start->y-$end->y)/2,
        $end->x,
        $start->y-($start->y-$end->y)/2,
        $this->lineColor);
      ImageLine($this->img,
        $end->x,
        $start->y-($start->y-$end->y)/2,
        $end->x,
        $end->y,
        $this->lineColor);
    }
    else {
      ImageLine($this->img,
        $start->x,
        $start->y,
        $start->x+($end->x-$start->x)/2,
        $start->y,
        $this->lineColor);
      ImageLine($this->img,
        $start->x+($end->x-$start->x)/2,
        $start->y,
        $start->x+($end->x-$start->x)/2,
        $end->y,
        $this->lineColor);
      ImageLine($this->img,
        $start->x+($end->x-$start->x)/2,
        $end->y,
        $end->x,
        $end->y,
        $this->lineColor);
    }
  }

  /**
   * Calculate line end points.
   * @param $poid The parent object's object id.
   * @param $oid The object's object id.
   * @return Array containing start and end position
   */
  private function calculateEndPoints($poid, $oid) {
    // from child...
    if ($this->map["type"] == MAPTYPE_HORIZONTAL) {
      // connect from mid top...
      $x1 = $this->map[$oid]->x * $this->xscale + ($this->labelDim['right'] - $this->labelDim['left'])/2 + $this->border;
      $y1 = $this->map[$oid]->y * $this->yscale + $this->border - 1;
    }
    else {
      // connect from mid left...
      $x1 = $this->map[$oid]->x * $this->xscale + $this->border - 1;
      $y1 = $this->map[$oid]->y * $this->yscale + ($this->labelDim['bottom'] - $this->labelDim['top'])/2 + $this->border;
    }
    // ...to parent
    if ($this->map["type"] == MAPTYPE_HORIZONTAL) {
      // ...to mid bottom
      $x2 = $this->map[$poid]->x * $this->xscale + ($this->labelDim['right'] - $this->labelDim['left'])/2 + $this->border;
      $y2 = $this->map[$poid]->y * $this->yscale + ($this->labelDim['bottom'] - $this->labelDim['top']) + $this->border + 1;
    }
    else {
      // ...to mid right
      $x2 = $this->map[$poid]->x * $this->xscale + $this->labelDim['right'] - $this->labelDim['left'] + $this->border + 1;
      $y2 = $this->map[$poid]->y * $this->yscale + ($this->labelDim['bottom'] - $this->labelDim['top'])/2 + $this->border;
    }
    return array(new Position($x1, $y1, 0), new Position($x2, $y2, 0));
  }
}
?>
