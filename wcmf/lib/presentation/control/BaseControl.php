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
namespace wcmf\lib\presentation\control;

use wcmf\lib\presentation\IView;
use wcmf\lib\presentation\control\Control;

/**
 * BaseControl is the default control implementation
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BaseControl extends Control
{
  /**
   * @see Control::assignViewValues
   */
  protected function assignViewValues(IView $view) {}
}
?>