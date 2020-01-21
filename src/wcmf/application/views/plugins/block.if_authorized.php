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
use wcmf\lib\core\ObjectFactory;

/**
 * Render content based on authorization.
 *
 * Example:
 * @code
 * {if_authorized resource="Category" action="delete"}
 *   ... content only visible
 *   for authorized users ...
 * {/if_authorized}
 * @endcode
 *
 * @note Works only for local files.
 *
 * @param $params Array with keys:
 *   - resource: The resource to authorize (e.g. class name of the Controller or OID)
 *   - context: The context in which the action takes place
 *   - action: The action to process
 *   - alternative_content: The content to display if not authorized
 * @param $content
 * @param $template Smarty_Internal_Template
 * @param $repeat
 * @return String
 */
function smarty_block_if_authorized($params, $content, \Smarty_Internal_Template $template, &$repeat) {
  if(!$repeat) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    if ($permissionManager->authorize($params['resource'], $params['context'], $params['action'])) {
      return $content;
    }
    else {
      return $params['alternative_content'];
    }
  }
}
?>