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
 * $Id: class.Control.php -1   $
 */
require_once(WCMF_BASE."wcmf/lib/presentation/control/class.Control.php");

/**
 * @class ListControl
 * @ingroup Presentation
 * @brief ListControl handles static lists
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListControl extends Control
{
  /**
   * @see Control::assignViewValues
   */
  protected function assignViewValues(IView $view)
  {
    // get the translated value
    $listMap = $view->getTemplateVars('listMap');
    $value = $view->getTemplateVars('value');
    $view->assign('translatedValue', $listMap[$value]);
  }
}
?>
