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

/**
 * Translate a static text.
 *
 * Example:
 * @code
 * {translate text="Logged in as %0% since %1%" r0=$login r1=$logindate}
 * @endcode
 *
 * @param $params Array with keys:
 *        - text: The text to translate
 *        - r0, r1, ...: Values for text variables (%0%, %1%, ...)
 *        - lang: The language to translate to
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_translate($params, Smarty_Internal_Template $template) {
  $variables = array();
  foreach (array_keys($params) as $key) {
    if (preg_match("/^r[0-9]+$/", $key)) {
      $variables[] = $params[$key];
    }
  }
  $message = ObjectFactory::getInstance('message');
  $value = isset($params['text']) ? $message->getText($params['text'], $variables, isset($params['lang']) ? $params['lang'] : null) : "";
  echo $value;
}
?>