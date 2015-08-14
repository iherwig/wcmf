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
use wcmf\lib\util\URIUtil;

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
function smarty_function_translate_url($params, \Smarty_Internal_Template $template) {
  $url = $params['url'];
  $base = $params['base'];

  $urls = URIUtil::translate($url, $base);

  $result = $urls['relative'];
  if (isset($params['absolute']) && $params['absolute']) {
    $result = $urls['absolute'];
  }
  if (isset($params['varname'])) {
    $template->assign($params['varname'], $result);
  }
  else {
    echo $result;
  }
}
?>