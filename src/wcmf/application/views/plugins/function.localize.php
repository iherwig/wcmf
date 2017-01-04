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

/**
 * Extract the language dependent value of a node attribute.
 *
 * Example:
 * @code
 * {localize node=$project attribute="text" lang="en"}
 * @endcode
 *
 * @param $params Array with keys:
 *        - node: The Node instance to extract the value from
 *        - attribute: The name of the attribute (will be appended with "_$lang")
 *        - lang: Language of the attribute
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_localize(array $params, Smarty_Internal_Template $template) {
  $language = $params['lang'];
  $node = $params['node'];
  $attribute = $params['attribute'];

  $defaultAttr = $attribute.'_'.$language;
  $defaultValue = $node->getValue($defaultAttr);
  if ($defaultValue) {
    return $defaultValue;
  }
  else {
    $fallbackAttr = $attribute.'_de';
    return $node->getValue($fallbackAttr);
  }
}
?>