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
 * Output a configuration value.
 *
 * Example:
 * @code
 * {configvalue key="exportDir" section="cms"}
 * @endcode
 *
 * @param $params Array with keys:
 *        - key: The key in the configuration section
 *        - section: The name of the configuration section
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_configvalue(array $params, Smarty_Internal_Template $template) {
  $config = ObjectFactory::getInstance('configuration');
  echo $config->getValue($params['key'], $params['section']);
}
?>