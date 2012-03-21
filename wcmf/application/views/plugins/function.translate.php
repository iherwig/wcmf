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

use wcmf\lib\i18n\Message;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.translate.php
* Type:     function
* Name:     translate
* Purpose:  translate a text
* Usage:    e.g. {translate text="Logged in as %1% since %2%" r0="$login" r1="$logindate" [varname="loginText"]}
* -------------------------------------------------------------
*/
function smarty_function_translate($params, &$smarty)
{
  $variables = array();
  foreach (array_keys($params) as $key)
  {
    if (preg_match("/^r[0-9]+$/", $key)) {
      array_push($variables, $params[$key]);
    }
  }
  $value = Message::get($params['text'], $variables, $params['lang']);
  if (isset($params['varname'])) {
    $smarty->assign($params['varname'], $value);
  }
  else {
  	echo $value;
  }
}
?>