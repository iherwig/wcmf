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
namespace wcmf\lib\pdf;

use setasign\Fpdi\Tfpdf\Fpdi;

if (!class_exists('setasign\Fpdi\Tfpdf\Fpdi')) {
    throw new \wcmf\lib\config\ConfigurationException(
            'wcmf\lib\pdf\PDF requires '.
            'Fpdi. If you are using composer, add setasign/tfpdf and setasign/fpdi '.
            'as dependency to your project');
}

/**
 * NOTE ON USING FONTS
 *
 * If tFPDF (https://github.com/Setasign/tFPDF) is used as base class, proceed as follows:
 *
 * 1. Create a fonts directory and set FPDF_FONTPATH accordingly:
 *
 * define('FPDF_FONTPATH', dirname(__FILE__).'/fonts/');
 *
 * 2. Create a unifont directory inside the font directory and put ttfonts.php from the tFPDF package inside
 * 3. Put *.ttf fonts inside the unifont directory (no need for any conversion) and add them to the PDF instance:
 *
 * $this->AddFont('Roboto-Regular', '', 'Roboto-Regular.ttf', true);
 *
 * (NOTE the 3rd parameter holds the font file name and the 4th parameter is set to true)
 */


/**
 * PDF extends setasign\Fpdi\Tfpdf\Fpdi.
 *
 * @note This class requires setasign\Fpdi\Tfpdf\Fpdi
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PDF extends Fpdi {

  private $pageStarted = false;
  private $pageEnded = false;
  private $usedTemplateId = null;

  /**
   * Overridden to set the template on the page
   */
  public function Header() {
    parent::Header();
    if ($this->usedTemplateId) {
      $this->useTemplate($this->usedTemplateId);
    }
  }

  /**
   * Overridden to set the template id
   * @param $templateId
   */
  public function useTemplate($tpl, $x=0, $y=0, $width=null, $height=null, $adjustPageSize=false) {
    parent::useTemplate($tpl);
    $this->usedTemplateId = $tpl;
  }

  /**
   * Call this method when rendering a new page
   */
  public function startPage() {
    $this->pageStarted = true;
    $this->pageEnded = false;
  }

  /**
   * Call this method when rendering a page finished
   */
  public function endPage() {
    $this->pageEnded = true;
    $this->pageStarted = false;
  }

  /**
   * Determine if a new page started
   * @return Boolean
   */
  public function isPageStarted() {
    return $this->pageStarted;
  }

  /**
   * Determine if a page finished
   * @return Boolean
   */
  public function isPageEnded() {
    return $this->pageEnded;
  }

  /**
   * Move the render position down by given units
   * @param $units The number of units to move
   */
  public function moveDown($units) {
    $this->SetY($units+$this->GetY());
  }

  /**
   * Move the render position right by given units
   * @param $units The number of units to move
   */
  public function moveRight($units) {
    $this->SetX($units+$this->GetX());
  }

  /**
   * Computes the number of lines a MultiCell of width w will take
   * instead of NbLines it correctly handles linebreaks
   * @param $width The width
   * @param $text The text
   */
  public function numberOfLines($width, $text) {
    $nbLines = 0;
    $lines = preg_split('/\n/', $text);
    foreach ($lines as $line) {
      $nbLines += $this->NbLines($width, $line);
    }
    return $nbLines;
  }

  /**
   * The following code is taken from FPDF Add-On 'Clipping'
   * @see http://fpdf.de/Addon-78-clipping.html
   */

  /**
   * Create a clipping area that restricts the display and prevents any elements from showing outside of it
   * @param $x
   * @param $y
   * @param $w
   * @param $h
   * @param $outline
   */
  public function ClippingRect($x, $y, $w, $h, $outline=false) {
    $op = $outline ? 'S' : 'n';
    $this->_out(sprintf('q %.2F %.2F %.2F %.2F re W %s', $x*$this->k, ($this->h-$y)*$this->k, $w*$this->k, -$h*$this->k, $op));
  }

  /**
   * Close the clipping area created with ClippingRect()
   */
  public function UnsetClipping() {
    $this->_out('Q');
  }

  /**
   * The following code is taken from FPDF Add-On 'Transformations'
   * @see http://fpdf.de/Addon-79-transformations.html
   */

  /**
   * Start a transformation (Scale, Translate, Rotate calls follow)
   */
  public function StartTransform() {
    // save the current graphic state
    $this->_out('q');
  }

  /**
   * Scale the following content
   * @param $s_x
   * @param $s_y
   * @param $x
   * @param $y
   */
  public function Scale($s_x, $s_y, $x='', $y='') {
    if ($x === '') {
      $x = $this->x;
    }
    if ($y === '') {
      $y = $this->y;
    }
    if ($s_x == 0 || $s_y == 0) {
      $this->Error('Please use values unequal to zero for Scaling');
    }
    $y = ($this->h-$y)*$this->k;
    $x *= $this->k;
    // calculate elements of transformation matrix
    $s_x /= 100;
    $s_y /= 100;
    $tm[0] = $s_x;
    $tm[1] = 0;
    $tm[2] = 0;
    $tm[3] = $s_y;
    $tm[4] = $x*(1-$s_x);
    $tm[5] = $y*(1-$s_y);
    // scale the coordinate system
    $this->Transform($tm);
  }

  /**
   * Move the following content
   * @param $t_x
   * @param $t_y
   */
  public function Translate($t_x, $t_y) {
    // calculate elements of transformation matrix
    $tm[0]=1;
    $tm[1]=0;
    $tm[2]=0;
    $tm[3]=1;
    $tm[4]=$t_x*$this->k;
    $tm[5]=-$t_y*$this->k;
    // translate the coordinate system
    $this->Transform($tm);
  }

  /**
   * Rotate the following content
   * @param $angle
   * @param $x
   * @param $y
   */
  public function Rotate($angle, $x='', $y='') {
    if ($x === '') {
      $x = $this->x;
    }
    if ($y === '') {
      $y = $this->y;
    }
    $y = ($this->h-$y)*$this->k;
    $x *= $this->k;
    // calculate elements of transformation matrix
    $tm[0] = cos(deg2rad($angle));
    $tm[1] = sin(deg2rad($angle));
    $tm[2] = -$tm[1];
    $tm[3] = $tm[0];
    $tm[4] = $x+$tm[1]*$y-$tm[0]*$x;
    $tm[5] = $y-$tm[0]*$y-$tm[1]*$x;
    // rotate the coordinate system around ($x,$y)
    $this->Transform($tm);
  }

  private function Transform($tm) {
    $this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F cm', $tm[0], $tm[1], $tm[2], $tm[3], $tm[4], $tm[5]));
  }

  /**
   * End a transformation
   */
  function StopTransform() {
    // restore previous graphic state
    $this->_out('Q');
  }

  /**
   * The following code is taken from FPDF Add-On 'Table with MultiCells'
   * @see http://fpdf.de/Addon-3-table-with-multicells.html
   */

  /**
   * If the height h would cause an overflow, add a new page immediately
   * @param $h The height
   * @return Boolean whether a new page was inserted or not
   */
  public function CheckPageBreak($h) {
    if($this->GetY()+$h>$this->PageBreakTrigger) {
      $this->AddPage($this->CurOrientation);
      return true;
    }
    return false;
  }

  /**
   * Computes the number of lines a MultiCell of width w will take
   * @param $w The width
   * @param $txt The text
   */
  public function NbLines($w, $txt) {
    if ($w==0) {
      $w = $this->w-$this->rMargin-$this->x;
    }
    $wmax = $w-2*$this->cMargin;
    $s = str_replace("\r", '', $txt);
    $nb = strlen($s);
    if ($nb>0 && $s[$nb-1]=="\n") {
      $nb--;
    }
    $sep = -1;
    $i = 0;
    $j = 0;
    $l = 0;
    $nl = 1;
    while ($i<$nb) {
      $c = $s[$i];
      if ($c=="\n") {
        $i++;
        $sep = -1;
        $j = $i;
        $l = 0;
        $nl++;
        continue;
      }
      if ($c==' ') {
        $sep = $i;
      }
      $l += $this->GetStringWidth($c);
      if ($l>$wmax) {
        if ($sep==-1) {
          if ($i==$j) {
            $i++;
          }
        }
        else {
          $i = $sep+1;
        }
        $sep = -1;
        $j = $i;
        $l = 0;
        $nl++;
      }
      else {
        $i++;
      }
    }
    return $nl;
  }
}
?>
