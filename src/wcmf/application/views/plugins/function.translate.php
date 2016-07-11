<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\core\ObjectFactory;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.translate.php
* Type:     function
* Name:     translate
* Purpose:  translate a text
* Usage:    e.g. {translate text="Logged in as %0% since %1%" r0="$login" r1="$logindate" [varname="loginText"]}
* -------------------------------------------------------------
*/
function smarty_function_translate($params, \Smarty_Internal_Template $template) {
  $variables = array();
  foreach (array_keys($params) as $key) {
    if (preg_match("/^r[0-9]+$/", $key)) {
      $variables[] = $params[$key];
    }
  }
  $message = ObjectFactory::getInstance('message');
  $value = isset($params['text']) ? $message->getText($params['text'], $variables, isset($params['lang']) ? $params['lang'] : null) : "";
  if (isset($params['varname'])) {
    $template->assign($params['varname'], $value);
  }
  else {
    echo $value;
  }
}
?>