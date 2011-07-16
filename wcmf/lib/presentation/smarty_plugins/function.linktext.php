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
require_once(WCMF_BASE."wcmf/lib/presentation/InternalLink.php");

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.linktext.php
* Type:     function
* Name:     linktext
* Purpose:  extract the link name from an url (get the corresponding display value of an internal link,
*           strip http://)
* Usage:    e.g. {linktext url="javascript:doDisplay('Category:285'); submitAction('')"}
* -------------------------------------------------------------
*/
function smarty_function_linktext($params, &$smarty)
{
  $url = $params['url'];
  if (InternalLink::isLink($url))
  {
    // get the display value
    $persistenceFacade = &PersistenceFacade::getInstance();
    $node = $persistenceFacade->load(InternalLink::getReferencedOID($url), BUILDDEPTH_SINGLE);
    $value = $node->getDisplayValue();
  }
  else 
    $value = preg_replace('/^http:\/\//', '', $url);
  
  echo $value;
}
?>