/** 
 * Include this file to have the functionality that is provided 
 * by the wCMF Message class in JavaScript.
 * 
 * For localization provide the get paramter 'lang'
 */

/* global localization variable, filled with all known messages for lang */
var localization = {
<?php
  // get language from get parameter
  $lang = $_GET['lang'];
  
  // write all message strings
  foreach (Message::getAll($lang) as $key => $value)
    echo '  "'.str_replace('"', '\"', $key).'": "'.str_replace('"', '\"', $value).'",'."\n";
  // last line - ignore
  echo '  "-": "-"'."\n";
?>
};

/**
 * The Message class is used to translate texts
 */
Message = function() {};
Message = {
  /**
   * Translate a text
   */
  get: function(/* message id */message, /* array with replacements */parameters) 
  {
    // get the localized message
    var localizedMessage = message;
    if (localization[message] != undefined)
      localizedMessage = localization[message];

    // replace any parameters given
    if (parameters) {
      var paramCount = parameters.length;
      for(var i=1; i<=paramCount; i++)
        localizedMessage = localizedMessage.replace(new RegExp("\\%"+i+"\\%", "gi"), parameters[i-1]);
    }

    return localizedMessage;
  }
};
