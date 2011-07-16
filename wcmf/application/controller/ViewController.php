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
require_once(WCMF_BASE."wcmf/lib/presentation/Controller.php");

/**
 * @class ViewController
 * @ingroup Controller
 * @brief ViewController is a controller that has no logic.
 * It is used to display a static view only.
 * 
 * <b>Input actions:</b>
 * - unspecified: Present the view
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ViewController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return true;
  }
  /**
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $this->_response->setAction('ok');
    return false;
  }
}
?>
