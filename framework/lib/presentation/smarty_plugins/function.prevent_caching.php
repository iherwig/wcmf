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

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.prevent_caching.php
* Type:     function
* Name:     prevent_caching
* Purpose:  prevents caching of an url by adding a unique parameter to the url
*           (default parameter name is chacheKiller, to change it pass a name in the
            'name' parameter)
* Usage:    e.g. {prevent_cache url="title.gif"} or {prevent_cache url="title.gif" name="uid"}
* -------------------------------------------------------------
*/
function smarty_function_prevent_caching($params, &$smarty)
{
  $variables = array();
  if (array_key_exists('name', $params))
    $params['name'] = 'cahceKiller';
      
  echo $params['url']."?".$params['name']."=".uniqid((double)microtime()*1000000,1);
}
?>