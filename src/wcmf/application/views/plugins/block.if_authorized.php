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
* File:     block.if_authorized.php
* Type:     block
* Name:     if_authorized
* Purpose:  render content based on authorization
* Parameters: resource The resource to authorize (e.g. class name of the Controller or OID).
*             context The context in which the action takes place.
*             action The action to process.
*             alternative_content The content to display if not authorized
* Usage:    {if_authorized resource="Category" action="delete"}
*               ... content only visible
*               for authorized users ...
*           {/if_authorized}
*
* Author:   Ingo Herwig <ingo@wemove.com>
* -------------------------------------------------------------
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