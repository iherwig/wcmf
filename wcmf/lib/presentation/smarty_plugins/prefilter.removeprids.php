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
namespace wcmf\lib\presentation\smarty_plugins;

/*
* Smarty plugin
* -------------------------------------------------------------
* File: prefilter.removeprids.php
* Type: prefilter
* Name: removeprids
* Purpose: Remove protected region ids (used by wCMFGenerator).
* -------------------------------------------------------------
*/
function smarty_prefilter_removeprids($tpl_source, &$smarty)
{
  // remove protected regions
  $tpl_source = preg_replace("/<!-- PROTECTED REGION .*? -->/U", "", $tpl_source);

  // remove any wCMFGenerator generated comments
  return preg_replace("/<!--.*?wCMFGenerator.*?-->/s", "", $tpl_source);
}
?>
