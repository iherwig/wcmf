<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\pdf;

/**
 * PDF extends FPDF/FPDI.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PDF extends FPDI {

  var $_pageStarted = false;
  var $_pageEnded = false;

  /**
   * Overriden to set the template on the page
   */
  function Header() {
    parent::Header();
    $this->useTemplate($this->tpl);
  }

  /**
   * Call this method when rendering a new page
   */
  function startPage() {
    $this->_pageStarted = true;
    $this->_pageEnded = false;
  }

  /**
   * Call this method when rendering a page finished
   */
  function endPage() {
    $this->_pageEnded = true;
    $this->_pageStarted = false;
  }

  /**
   * Determine if a new page started
   * @return True/False
   */
  function isPageStarted() {
    return $this->_pageStarted;
  }

  /**
   * Determine if a page finished
   * @return True/False
   */
  function isPageEnded() {
    return $this->_pageEnded;
  }

  /**
   * Move the render position down by given units
   * @param units The number of units to move
   */
  function moveDown($units) {
    $this->SetY($units+$this->GetY());
  }

  /**
   * Move the render position right by given units
   * @param units The number of units to move
   */
  function moveRight($units) {
    $this->SetX($units+$this->GetX());
  }

  /**
   * Computes the number of lines a MultiCell of width w will take
   * instead of NbLines it correctly handles linebreaks
   * @param width The width
   * @param text The text
   */
  function numberOfLines($width, $text) {
    $nbLines = 0;
    $lines = preg_split('/\n/', $text);
    foreach ($lines as $line) {
      $nbLines += $this->NbLines($width, $line);
    }
    return $nbLines;
  }

  /**
   * The following code is taken from FPDF Add-On 'Table with MultiCells'
   * @see http://www.fpdf.de/downloads/addons/3/
   */

  /**
   * If the height h would cause an overflow, add a new page immediately
   * @param h The height
   * @return Boolean whether a new page was inserted or not
   */
  function CheckPageBreak($h) {
    if($this->GetY()+$h>$this->PageBreakTrigger) {
      $this->AddPage($this->CurOrientation);
      return true;
    }
    return false;
  }

  /**
   * Computes the number of lines a MultiCell of width w will take
   * @param w The width
   * @param txt The text
   */
  function NbLines($w, $txt) {
    $cw=&$this->CurrentFont['cw'];
    if($w==0) {
      $w=$this->w-$this->rMargin-$this->x;
    }
    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    $s=str_replace("\r",'',$txt);
    $nb=strlen($s);
    if($nb>0 && $s[$nb-1]=="\n") {
      $nb--;
    }
    $sep=-1;
    $i=0;
    $j=0;
    $l=0;
    $nl=1;
    while($i<$nb) {
      $c=$s[$i];
      if($c=="\n") {
        $i++;
        $sep=-1;
        $j=$i;
        $l=0;
        $nl++;
        continue;
      }
      if($c==' ') {
        $sep=$i;
      }
      $l+=$cw[$c];
      if($l>$wmax) {
        if($sep==-1) {
          if($i==$j) {
            $i++;
          }
        }
        else {
          $i=$sep+1;
        }
        $sep=-1;
        $j=$i;
        $l=0;
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
