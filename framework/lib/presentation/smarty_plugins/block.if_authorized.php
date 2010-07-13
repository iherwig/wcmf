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
function smarty_block_if_authorized($params, $content, &$smarty)
{
  if(!$repeat)
  {
    $rightsManager = RightsManager::getInstance();
    if ($rightsManager->authorize($params['resource'], $params['context'], $params['action']))
    {
      return $content;
    }
    else
    {
      return $params['alternative_content'];
    }
  }
}
?>