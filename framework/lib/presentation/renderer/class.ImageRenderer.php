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
 * $Id: class.ValueRenderer.php -1   $
 */
require_once(WCMF_BASE."wcmf/lib/presentation/renderer/class.ValueRenderer.php");

/**
 * @class ImageRenderer
 * @ingroup Presentation
 * @brief ImageRenderer is used to render values with display type 'image'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ImageRenderer extends ValueRenderer
{
  /**
   * @see ValueRenderer::assignViewValues
   */
  protected function assignViewValues(IView $view) 
  {
    $value = $view->get_template_vars('value');
    if (file_exists($value))
    {
      $properties = getimagesize($value);
      $view->assign('width', $properties[0]);
      $view->assign('height', $properties[1]);
      $view->assign('filename', basename($value));
      $view->assign('exists', true);
    }
    else {
      $view->assign('exists', false);
    }
  }
}
?>
