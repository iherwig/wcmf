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
 * $Id: class.TextControl.php -1   $
 */
require_once(WCMF_BASE."wcmf/lib/presentation/control/Control.php");
require_once(WCMF_BASE."wcmf/lib/util/Obfuscator.php");

/**
 * @class AsyncListControl
 * @ingroup Presentation
 * @brief AsyncListControl handles lists that are retrieved from the server
 * asynchronously.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AsyncListControl extends Control
{
  /**
   * @see Control::assignViewValues()
   */
  protected function assignViewValues(IView $view)
  {
    $inputType = $view->getTemplateVars('inputType');
    if (preg_match("/^select.*?#async\:(.+)$/", $inputType, $matches))
    {
      $list = $matches[1];
      // get the entity type to list and an optional filter
      $parts = preg_split('/\|/', $list);
      $entityType = array_shift($parts);
      $filter = join('', $parts);
      $view->assign('entityType', $entityType);
      $view->assign('filter', $filter);
      $view->assign('obfuscator', Obfuscator::getInstance());

      // get the translated value
      $listMap = $view->getTemplateVars('listMap');
      $value = $view->getTemplateVars('value');
      $view->assign('translatedValue', $listMap[$value]);
    }
  }
}
?>
