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

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     block.repeat.php
* Type:     block
* Name:     repeat
* Purpose:  repeat a template block a given number of times and replace
*           {literal}{$index}{/literal} by the current index
*           (NOTE: $index has to be enclosed by literal)
* Parameters: count [required]  - number of times to repeat
*             assign [optional] - variable to collect output
*             startindex [optional] - index value to start from
*             strformat [optional] - format string to apply on index
*             separator [optional] - separator to be added inbetween
* Usage:    {repeat count=$content->getValue("numPages") startindex="1" strformat="%02s" separator=" | "}
*               ... text to repeat {literal}{$index}{/literal} ...
*           {/repeat}
*
* Author:   Scott Matthewman <scott@matthewman.net>
*           Ingo Herwig <ingo@wemove.com> (index, separator enhancement)
* -------------------------------------------------------------
*/
function smarty_block_repeat($params, $content, &$smarty)
{
    if (!empty($content))
    {
        $intCount = intval($params['count']);
        if($intCount < 0)
        {
            $smarty->trigger_error("block: negative 'count' parameter");
            return;
        }

        $strRepeat = '';
        for ($i=0; $i<$intCount; $i++)
        {
          $index = $i + intval($params['startindex']);
          if (isset($params['strformat']))
            $indexStr = sprintf($params['strformat'], $index);
          else
            $indexStr = $index;

          $strRepeat .= str_replace('{$index}', $indexStr, $content);

          if (isset($params['separator']) && $i<$intCount-1)
            $strRepeat .= $params['separator'];
        }

        if (!empty($params['assign']))
            $smarty->assign($params['assign'], $strRepeat);
        else
            echo $strRepeat;
    }
}
?>