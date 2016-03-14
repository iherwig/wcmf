<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.prevent_caching.php
* Type:     function
* Name:     prevent_caching
* Purpose:  prevents caching of an url by adding a unique parameter to the url
*           (default parameter name is cacheKiller, to change it pass a name in the
            'name' parameter)
* Usage:    e.g. {prevent_cache url="title.gif"} or {prevent_cache url="title.gif" name="uid"}
* -------------------------------------------------------------
*/
function smarty_function_prevent_caching($params, \Smarty_Internal_Template $template) {
  if (isset($params['name'])) {
    $params['name'] = 'cacheKiller';
  }
  echo $params['url']."?".$params['name']."=".uniqid((double)microtime()*1000000, 1);
}
?>