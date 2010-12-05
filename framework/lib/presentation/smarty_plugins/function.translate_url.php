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
require_once(WCMF_BASE."wcmf/lib/util/class.URIUtil.php");

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.translate_url.php
* Type:     function
* Name:     translate_url
* Purpose:  translate a relative url into an absolute one. The 'base' parameter
*           defines the relative path from the executed script to the location 
*           from which the relative path given in the 'url' parameter is seen
*           from. The result maybe printed directly or assigned to a smarty variable.
*           If the parameter 'absolute' is set to true, the url will be given absolute
*           (the default value is false which means the url is relative).
*           Example: If an image is stored in the database under the location 
*           '../../media/image1.png' as seen from http://www.example.com/cms/application/main.php 
*           and the image should be displayed in a template that is displayed by the script 
*           http://www.example.com/index.php than the image url may be translated by using the 
*           following call:
* Usage:    {translate_url url=$image->getFile() base="cms/application/"} or
*           {translate_url url=$image->getFile() base="cms/application/" varname="imageFile" absolute=true}
* -------------------------------------------------------------
*/
function smarty_function_translate_url($params, &$smarty)
{
  $url = $params['url'];
  $base = $params['base'];

  $urls = URIUtil::translate($url, $base);

  $result = $urls['relative'];
  if (isset($params['absolute']) && $params['absolute']) {
    $result = $urls['absolute'];
  }
  if (isset($params['varname'])) {
    $smarty->assign($params['varname'], $result);
  }
  else {
  	echo $result;
  }
}
?>