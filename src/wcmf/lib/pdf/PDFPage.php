<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\pdf;

/**
 * PDFPage instances define the content of a pdf page by using a
 * set of FPDF/FPDI commands inside the PDFPage::render method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PDFPage {

  /**
   * Render data onto a pdf.
   * @param $pdf FPDF/FPDI instance to render onto
   * @param $page The page number in the pdf document
   * @param $data An optional data object to get data from.
   */
  public function render($pdf, $page, $data=null);
}
?>
