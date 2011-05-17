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
 * $Id: Message.js.php 1334 2011-05-17 00:15:05Z iherwig $
 */
require_once(WCMF_BASE."wcmf/lib/util/class.Message.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Application.php");

// initialize global variables
$application = Application::getInstance();
$application->setupGlobals();

// get all messages ($lang parameter is optional)
$lang = $_GET['lang'];
$messages = Message::getAll($lang);
?>
dojo.provide("wcmf.Message");

/**
 * @class Request The Message class is used to translate texts
 */
dojo.declare("wcmf.Message", null, {
});

wcmf.Message.messages = {
<?php
  // write all message strings
  foreach ($messages as $key => $value) {
    echo '  "'.str_replace('"', '\"', $key).'": "'.str_replace('"', '\"', $value).'",'."\n";
  }
  // last line - ignore
  echo '  "-": "-"'."\n";
?>
};
  
/**
 * Get a localized string.
 * 
 * @param message
 *            The string to localize
 * @param parameter
 *            An array with replacements for message variables
 */
wcmf.Message.get = function(message, parameters) {
  // get the localized message
  var localizedMessage = message;
  if (wcmf.Message.messages[message] != undefined) {
      localizedMessage = wcmf.Message.messages[message];
  }
  // replace any parameters given
  if (parameters) {
      var paramCount = parameters.length;
      for(var i=1; i<=paramCount; i++) {
        localizedMessage = localizedMessage.replace(new RegExp("\\%"+i+"\\%", "gi"), parameters[i-1]);
      }
  }
  return localizedMessage;
};
