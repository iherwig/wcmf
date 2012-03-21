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
 * $Id: modifier.textile.php 1148 2010-02-09 02:08:44Z iherwig $
 */
 namespace wcmf\lib\presentation\smarty_plugins;
 
/*
* Smarty plugin
* -------------------------------------------------------------
* File:     modifier.textile.php
* Type:     function
* Name:     textile
* Purpose:  use textile to generate HTML code from text
* Usage:    e.g. {$text|textile}
* -------------------------------------------------------------
*/
require_once(WCMF_BASE."wcmf/3rdparty/textile/classTextile.php");

function smarty_modifier_textile($text)
{
  $textile = new Textile();
  return $textile->TextileThis($text);
}
?>

