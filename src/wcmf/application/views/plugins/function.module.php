<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;

/**
 * Render a content module.
 *
 * Content modules must implement the 'wcmf\lib\presentation\ContentModule' interface
 * and are configured in the 'ContentModule' configuration section that maps the
 * content module name to the implementing class, e.g.:
 *
 * [ContentModule]
 * next_events = \app\src\lib\content\NextEvents
 * form = \app\src\lib\content\Form
 *
 * Module output is cached by default. To prevent it from being cached it must be
 * wrapped within a {nocache} tag.
 *
 * Usage example:
 * @code
 * {module name='next_events' ...} {* cache module output *}
 * {nocache}{module name='form' ...}{/nocache} {* don't cache module output *}
 * @endcode
 *
 * @param $params Array with keys:
 *        - name: The name of the module that must exist as a key in the configuration section named 'ContentModule'
 *        + additional module specific parameters
 *        NOTE: All variables from the including template are passed to the module template.
 * @param $template \Smarty\Template
 * @return String
 */
function smarty_function_module($params, \Smarty\Template $template) {
  $requiredInterface = 'wcmf\lib\presentation\ContentModule';
  $config = ObjectFactory::getInstance('configuration');
  $modules = $config->getSection('ContentModule');

  // search content module
  $name = $params['name'];
  $moduleClass = isset($modules[$name]) ? $modules[$name] : null;
  if ($moduleClass && class_exists($moduleClass) && in_array($requiredInterface, class_implements($moduleClass))) {
    $contentModule = new $moduleClass();
    $contentModule->initialize($template, $params);
  }
  else {
    LogManager::getLogger(__FILE__)->error('Content class \''.$moduleClass.'\' for content module \''.$name.'\' does not exist or does not implement interface \''.$requiredInterface.'\'');
  }

  // return content
  return $contentModule ? $contentModule->render() : '';
}
?>