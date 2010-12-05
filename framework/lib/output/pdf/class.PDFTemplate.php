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
require_once(WCMF_BASE."wcmf/lib/output/pdf/class.PDF.php");
require_once(WCMF_BASE."wcmf/lib/core/class.WCMFException.php");

/**
 * @class PDFTemplate
 * @ingroup Output
 * @brief PDFTemplate is used to output pdf files based on a given pdf template.
 * PDFTemplate uses FPDI/FPDF. PDFPage instances are used to render data onto
 * the template pages.
 *
 * The following example shows the usage:
 *
 * @code
 * // example Controller method to show a pdf download dialog
 * // Page1 extends PDFPage and defines what is rendered onto the template
 * function executeKernel()
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
class PDFTemplate
{
  var $_pdf = null;
  var $_tpl = null;
  var $_pages = array();
  var $_cycle = false;
  var $_data = null;

  /**
   * Constructor
   * @param pdf The PDF instance to render onto, defaults to PDF created with default constructor
   */
  function PDFTemplate(&$pdf)
  {
    if (!isset($pdf) || (!($pdf instanceof PDF)))
      $this->_pdf = new PDF();
    else
      $this->_pdf = &$pdf;
  }

  /**
   * Set the template filename
   * @param filename The name of the file
   */
  function setTemplate($filename)
  {
    $this->_tpl = $filename;
  }

  /**
   * Set the PDFPage instances used to write the content to the template.
   * The page instances will be used one after another: The 1 instance writes to the first
   * template page, the second to the second and so on. Use the cycle parameter to cycle the
   * instances (e.g. if the same data should be written to every template page, put one
   * instance into the pages array and set cycle to true)
   * @param pages An array of PDFPage instances
   * @param cycle True/False wether to cycle the PDFPage instances or not [default: false]
   * @param data An optional data object, that will passed to the PDFPage::render method [default: null]
   */
  function setPages($pages, $cycle=false, $data=null)
  {
    $this->_pages = $pages;
    $this->_cycle = $cycle;
    $this->_data = &$data;
  }

  /**
   * Output the pdf. Delegates to FPDF::Output()
   * @see http://www.fpdf.de/funktionsreferenz/Output/
   * @param name The name of the pdf file
   * @param dest The pdf destination ('I': browser inline, 'D': browser download, 'F': filesystem, 'S': string)
   * @return The document string in case of dest = 'S', nothing else
   */
  function output($name='', $dest='')
  {
    if ($this->_tpl == null)
    {
      WCMFException::throwEx("No PDF template provided. Use PDFTemplate::setTemplate.", __FILE__, __LINE__);
      return;
    }

    $pageIndex = 0;
    $numPages = $this->_pdf->setSourceFile($this->_tpl);
    for ($i=1; $i<=$numPages; $i++)
    {
      // add each page
      $tplIndex = $this->_pdf->ImportPage($i);
      $size = $this->_pdf->getTemplatesize($tplIndex);
      $this->_pdf->AddPage($size['h'] > $size['w'] ? 'P' : 'L');

      // render the PDFPage onto the template page
      if ($pageIndex < sizeof($this->_pages))
      {
        $curPage = &$this->_pages[$pageIndex];
        if ($curPage instanceof PDFPage)
        {
          $this->_pdf->startPage();
          $curPage->render($this->_pdf, $i, $this->_data);
          $this->_pdf->endPage();
        }
      }
      $pageIndex++;

      // cycle pages if required
      if ($this->_cycle && $pageIndex == sizeof($this->_pages))
        $pageIndex = 0;
    }

    // output the pdf
    return $this->_pdf->Output($name, $dest);
  }
}
?>
