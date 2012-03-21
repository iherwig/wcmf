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
namespace wcmf\application\controller;

use wcmf\application\controller\LongTaskController;

/**
 * SimpleLongTaskController is a controller demonstrating the use
 * of LongTaskController for cutting a long task into a fixed number
 * of smaller tasks.
 *
 * @note This is an example implementation that creates 10 files
 *
 * <b>Input actions:</b>
 * - see LongTaskController
 *
 * <b>Output actions:</b>
 * - see LongTaskController
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SimpleLongTaskController extends LongTaskController
{
  // number of total steps
  var $NUM_STEPS = 10;

  /**
   * @see LongTaskController::getNumberOfSteps()
   */
  function getNumberOfSteps()
  {
    return $this->NUM_STEPS;
  }
  /**
   * @see LongTaskController::getDisplayText()
   */
  function getDisplayText($step)
  {
    return "Creating file number ".$step." ...";
  }
  /**
   * @see LongTaskController::getSummaryText()
   * The default implementation returns an empty string
   */
  function getSummaryText()
  {
    return "";
  }
  /**
   * @see LongTaskController::processPart()
   */
  function processPart()
  {
    // do some processing depending on state here
    $curNum = sprintf("%04s",$this->getStepNumber());
    $fh = fopen("result".$curNum.".txt", "a");
    fputs($fh, date("F j, Y, g:i a").": SimpleLongTaskController created file #".$curNum."\n");
    fclose($fh);
  }
}
?>
