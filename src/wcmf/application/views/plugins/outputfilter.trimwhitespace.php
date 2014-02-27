<?php
 /**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     smarty_outputfilter_trimwhitespace
* -------------------------------------------------------------
*/
function smarty_outputfilter_trimwhitespace($output, \Smarty_Internal_Template $template) {
    // Pull out the script blocks
    preg_match_all("!<script[^>]*?>.*?</script>!is", $output, $match);
    $_script_blocks = $match[0];
    $output = preg_replace("!<script[^>]*?>.*?</script>!is",
                           '@@@SMARTY:TRIM:SCRIPT@@@', $output);

    // Pull out the pre blocks
    preg_match_all("!<pre[^>]*?>.*?</pre>!is", $output, $match);
    $_pre_blocks = $match[0];
    $output = preg_replace("!<pre[^>]*?>.*?</pre>!is",
                           '@@@SMARTY:TRIM:PRE@@@', $output);

    // Pull out the textarea blocks
    preg_match_all("!<textarea[^>]*?>.*?</textarea>!is", $output, $match);
    $_textarea_blocks = $match[0];
    $output = preg_replace("!<textarea[^>]*?>.*?</textarea>!is",
                           '@@@SMARTY:TRIM:TEXTAREA@@@', $output);

    // remove all leading spaces, tabs and carriage returns NOT
    // preceeded by a php close tag.
    $output = trim(preg_replace('/((?<!\?>)\n)[\s]+/m', '\1', $output));

    // replace textarea blocks
    smarty_outputfilter_trimwhitespace_replace("@@@SMARTY:TRIM:TEXTAREA@@@",$_textarea_blocks, $output);

    // replace pre blocks
    smarty_outputfilter_trimwhitespace_replace("@@@SMARTY:TRIM:PRE@@@",$_pre_blocks, $output);

    // replace script blocks
    smarty_outputfilter_trimwhitespace_replace("@@@SMARTY:TRIM:SCRIPT@@@",$_script_blocks, $output);

    return $output;
}

function smarty_outputfilter_trimwhitespace_replace($search_str, $replace, &$subject) {
  $_len = strlen($search_str);
  $_pos = 0;
  for ($_i=0, $_count=count($replace); $_i<$_count; $_i++) {
    if (($_pos=strpos($subject, $search_str, $_pos))!==false) {
      $subject = substr_replace($subject, $replace[$_i], $_pos, $_len);
    }
    else {
      break;
    }
  }
}
?>
