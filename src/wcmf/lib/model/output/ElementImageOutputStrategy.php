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
namespace wcmf\lib\model\output;

use wcmf\lib\model\output\ImageOutputStrategy;
use wcmf\lib\model\output\Position;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PersistentObject;

/**
 * ElementImageOutputStrategy outputs a tree of objects into an image file.
 * It must be configured with a map that was calculated by a LayoutVisitor.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ElementImageOutputStrategy extends ImageOutputStrategy {

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
  public function __construct($format, $file, $map, $lineType=self::LINETYPE_DIRECT, $scale=100,
          $aspect=0.5, $border=50, $usemap='') {

    parent::__construct($format, $file, $map, $lineType, $scale, $aspect, $border, $usemap);
    // define label dimensions relative to node connector position
    $this->labelDim['left'] = -10;
    $this->labelDim['top'] = -10;
    $this->labelDim['right'] = 80;
    $this->labelDim['bottom'] = 45;
    // define text position relative to node connector position
    $this->textPos['left'] = -5;
    $this->textPos['top'] = -8;
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    parent::writeHeader();
    // print legend
    $color = ImageColorAllocate($this->img, 0, 150, 0);
    $this->writeFilledBorderedRect(new Position($this->border, $this->border, 0),
                                   new Position($this->border+10, $this->border+10, 0), $this->bgColor, $color);
    ImageString($this->img, 1, $this->border+20, $this->border+2, "optional", $color);
    $color = $this->txtColor;
    $this->writeFilledBorderedRect(new Position($this->border, $this->border+20, 0),
                                   new Position($this->border+10, $this->border+30, 0), $this->bgColor, $color);
    ImageString($this->img, 1, $this->border+20, $this->border+22, "required", $color);
    $color = ImageColorAllocate($this->img, 150, 150, 150);
    for($i=2; $i>=0; $i--) {
      $this->writeFilledBorderedRect(new Position($this->border+2*$i, $this->border+40+2*$i, 0),
                                     new Position($this->border+10+2*$i, $this->border+50+2*$i, 0), $this->bgColor, $color);
    }
    ImageString($this->img, 1, $this->border+20, $this->border+42, "repetitive", $color);
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    $properties = $obj->getValueProperties($obj->getType());
    if (!strstr($obj->getType(), '->')) {
      $smallText = $obj->getType();
      $bigText = $properties['oid'];
      $color = $this->txtColor;
    }
    else {
      $smallText = substr ($obj->getType(), 0, strrpos ($obj->getType(), ":"));
      $bigText = substr (strrchr ($obj->getType(), ":"), 1);
      $color = ImageColorAllocate($this->img, 150, 150, 150);
    }

    $oid = $obj->getOID();
    $x = $this->map[$oid]->x * $this->xscale - $this->labelDim['left'] + $this->border;
    $y = $this->map[$oid]->y * $this->yscale - $this->labelDim['top'] + $this->border;

    if ($obj->getProperty('minOccurs') == 0) { // optional
      $color = ImageColorAllocate($this->img, 0, 150, 0);
    }

    // print box
    if ($obj->getProperty('maxOccurs') == 'unbounded' || $obj->getProperty('maxOccurs') > 1) {
      for($i=3; $i>=1; $i--) {
        $this->writeFilledBorderedRect(new Position($x + $this->labelDim['left']+5*$i, $y + $this->labelDim['top']+5*$i, 0),
                                new Position($x + $this->labelDim['right']+5*$i, $y + $this->labelDim['bottom']+5*$i, 0),
                                $this->bgColor, $color);
      }
    }
    // print label
    $this->writeFilledBorderedRect(new Position($x + $this->labelDim['left'], $y + $this->labelDim['top'], 0),
                            new Position($x + $this->labelDim['right'], $y + $this->labelDim['bottom'], 0),
                            $this->bgColor, $color);
    // write text
    ImageString($this->img, 2,
                $x + $this->textPos['left'],
                $y + $this->textPos['top'],
                $smallText,
                $color);
    ImageString($this->img, 1,
                $x + $this->textPos['left'],
                $y + $this->textPos['top']+15,
                "E: ".$properties['data_type'],
                $color);
    // write attribs
    $attribs = $obj->getValueNames(true);
    $i = 0;
    if (is_array($attribs)) {
      foreach ($attribs as $attrib) {
        ImageString($this->img, 1,
              $x + $this->textPos['left'],
              $y + $this->textPos['top']+25+10*$i,
              "A: ".$attrib,
              $color);
        $i++;
      }
    }
    ImageString($this->img, 45,
                $x + $this->textPos['left']+65,
                $y + $this->textPos['top']+37,
                $bigText,
                $color);

    // draw line
    $parent = $obj->getParent();
    if ($parent) {
      $this->drawConnectionLine($parent->getOID(), $oid);
    }
    // print map
    if ($this->usemap != '') {
      echo '<area shape="rect" coords="'.
        ($x + $this->labelDim['left']).','.
        ($y + $this->labelDim['top']).','.
        ($x + $this->labelDim['right']).','.
        ($y + $this->labelDim['bottom'] + 8*$this->map[$oid]->z).
        '" onclick="javascript:alert(\'Node OID: '.$obj->getOID().'\')" alt="'.$obj->getOID().'">'."\n";
    }
  }

  private function writeFilledBorderedRect($topleft, $bottomright, $bgcolor, $bordercolor) {
    ImageFilledRectangle($this->img, $topleft->x, $topleft->y, $bottomright->x, $bottomright->y, $bgcolor);
    ImageRectangle($this->img, $topleft->x, $topleft->y, $bottomright->x, $bottomright->y, $bordercolor);
  }
}
?>