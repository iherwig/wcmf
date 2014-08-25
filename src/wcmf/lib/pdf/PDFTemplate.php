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

use \RuntimeException;
use wcmf\lib\pdf\PDF;
use wcmf\lib\pdf\PDFPage;

/**
 * PDFTemplate is used to output pdf files based on a given pdf template.
 * PDFTemplate uses FPDI/FPDF. PDFPage instances are used to render data onto
 * the template pages.
 *
 * The following example shows the usage:
 *
 * @code
 * // example Controller method to show a pdf download dialog
 * // Page1 extends PDFPage and defines what is rendered onto the template
 * function doExecute()
 * {
 *   $template = new PDFTemplate(new MyPDF());
 *   // set the template
 *   $template->setTemplate('include/pdf/wcmf.pdf');
 *   // render Page1 on every second template page
 *   $template->setPages(array(new Page1(), null), true);
 *
 *   // output the final page
 *   $downloadfile = 'wcmf.pdf';
 *   header("Content-disposition: attachment; filename=$downloadfile");
 *   header("Content-Type: application/force-download");
 *   header("Content-Transfer-Encoding: binary");
 *   header("Pragma: no-cache");
 *   header("Expires: 0");
 *   echo $template->output($downloadfile, 'S');
 * }
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PDFTemplate {

  var $_pdf = null;
  var $_tpl = null;
  var $_pages = array();
  var $_cycle = false;
  var $_data = null;

  /**
   * Constructor
   * @param $pdf The PDF instance to render onto, defaults to PDF created with default constructor
   */
  public function __construct($pdf) {
    if (!isset($pdf) || (!($pdf instanceof PDF))) {
      $this->_pdf = new PDF();
    }
    else {
      $this->_pdf = $pdf;
    }
  }

  /**
   * Set the template filename
   * @param $filename The name of the file
   */
  public function setTemplate($filename) {
    $this->_tpl = $filename;
  }

  /**
   * Set the PDFPage instances used to write the content to the template.
   * The page instances will be used one after another: The 1 instance writes to the first
   * template page, the second to the second and so on. Use the cycle parameter to cycle the
   * instances (e.g. if the same data should be written to every template page, put one
   * instance into the pages array and set cycle to true)
   * @param $pages An array of PDFPage instances
   * @param $cycle Boolean whether to cycle the PDFPage instances or not (default: _false_)
   * @param $data An optional data object, that will passed to the PDFPage::render method (default: _null_)
   */
  public function setPages($pages, $cycle=false, $data=null) {
    $this->_pages = $pages;
    $this->_cycle = $cycle;
    $this->_data = &$data;
  }

  /**
   * Output the pdf. Delegates to FPDF::Output()
   * @see http://www.fpdf.de/funktionsreferenz/Output/
   * @param $name The name of the pdf file
   * @param $dest The pdf destination ('I': browser inline, 'D': browser download, 'F': filesystem, 'S': string)
   * @return The document string in case of dest = 'S', nothing else
   */
  public function output($name='', $dest='') {

    if ($this->_tpl == null) {
      throw new RuntimeException("No PDF template provided. Use PDFTemplate::setTemplate.");
    }

    $pageIndex = 0;
    $numPages = $this->_pdf->setSourceFile($this->_tpl);
    for ($i=1; $i<=$numPages; $i++) {
      // add each page
      $tplIndex = $this->_pdf->ImportPage($i);
      $size = $this->_pdf->getTemplatesize($tplIndex);
      $this->_pdf->AddPage($size['h'] > $size['w'] ? 'P' : 'L');

      // render the PDFPage onto the template page
      if ($pageIndex < sizeof($this->_pages)) {
        $curPage = &$this->_pages[$pageIndex];
        if ($curPage instanceof PDFPage) {
          $this->_pdf->startPage();
          $curPage->render($this->_pdf, $i, $this->_data);
          $this->_pdf->endPage();
        }
      }
      $pageIndex++;

      // cycle pages if required
      if ($this->_cycle && $pageIndex == sizeof($this->_pages)) {
        $pageIndex = 0;
      }
    }

    // output the pdf
    return $this->_pdf->Output($name, $dest);
  }
}
?>
