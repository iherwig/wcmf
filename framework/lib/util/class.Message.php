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
// require file only if run in wcmf enviroment
if (file_exists(BASE."wcmf/lib/util/class.InifileParser.php"))
  require_once(BASE."wcmf/lib/util/class.InifileParser.php");

/**
 * @class Message
 * @ingroup Util
 * @brief Use the Message class to output messages.
 * You need not instantiate a Message object
 * because the methods may be called like static
 * class methods e.g.
 * $translated = Message::get('text to translate')
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Message
{
  /**
   * The get() method is used to get a localized string.
   * This method uses the GNU gettext (see PHP manual), 
   * the localization directory must be given in the global variable $MESSAGE_LOCALE_DIR (configuration value 'localeDir' in section 'cms')
   * @note The language is determined in one of 3 ways (in this order):
   * -# use the value of the global variable $MESSAGE_LANGUAGE (configuration value 'language' in section 'cms')
   * -# use the value of the global variable $_SERVER['HTTP_ACCEPT_LANGUAGE']
   * -# use the value of the given lang parameter
   * @param message The message to translate (\%0%, \%1%, ... will be replaced by given parameters).
   * @param parameters An array of values for parameter substitution in the message.
   * @param domain The domain to get the text from, optional, default: 'main'.
   * @param lang The language, optional, default: ''.
   * @return The localized string
   * @note It is not recommended to use this method with concatenated strings because this
   * restricts the positions of words in translations. E.g. 'She was born in %1% on %2%'
   * translates to the german sentance 'Sie wurde am \%2% in \%1% geboren' with the variables
   * flipped.
   * @note since gettext sometimes is not reliable (caching problem), it is possible to 
   * use custom php arrays created from .po files. use wcmf/tools/po2array.php to create
   * the appropriate messages_$lang.php files in the locale directories. the usage of these arrays
   * is configurable by the ini file option 'usegettext' in section 'cms' if this option is set
   * to 0 the method tries to search for the appropriate array definition.
   */
  function get ($message, $parameters=null, $domain='', $lang='')
  {
    if (!file_exists(BASE."wcmf/lib/util/class.InifileParser.php"))
      return $message;
      
    global $MESSAGE_LANGUAGE;
    global $MESSAGE_LOCALE_DIR;
    
    // select language
    if ($lang == '')
    {
      if ($MESSAGE_LANGUAGE != '')
        $lang = $MESSAGE_LANGUAGE;
      else if ($lang == '')
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }      
    if ($domain == '')
      $domain = 'main';

    // convert lang to language_COUNTRY format if not already done
    $lang = preg_replace("/\-/", "_", $lang);
    $lang = preg_replace("/(\w+)_(\w+)/e", "'\\1_'.strtoupper('\\2')", $lang);
    // if _COUNTRY is missing, use language as country
    if (strpos($lang, '_') === false)
      $lang = $lang.'_'.strtoupper($lang);

    $parser = &InifileParser::getInstance();
    if (($useGetText = $parser->getValue('usegettext', 'cms')) === false)
      $useGetText = 1;
    
    if ($useGetText)
    {
      // see if gettext is installed
      if (function_exists(bindtextdomain) && function_exists(textdomain) && function_exists(gettext))
      {
        // get localized message
        putenv("LANGUAGE=".$lang);
        putenv("LANG=".$lang);
        putenv("LC_ALL=".$lang);
        setlocale(LC_ALL, $lang);
        bindtextdomain($domain, $MESSAGE_LOCALE_DIR);
        textdomain($domain);
        $localizedMessage = gettext($message);
      }
      else
        $localizedMessage = $message;
    }
    else 
    {
      // try to use custom array definitions as dictionary
      $messageFile = $MESSAGE_LOCALE_DIR.$lang."/LC_MESSAGES/messages_".$lang.".php";
      if (file_exists($messageFile))
      {
        require($messageFile); // require_once does not work here !!!
        if (${"messages_$lang"}[$message] != "")
          $localizedMessage = ${"messages_$lang"}[$message];
        else
          $localizedMessage = $message;
      }
      else
        $localizedMessage = $message;
    }

    // replace parameters
    preg_match_all("/%([0-9]+)%/", $localizedMessage, $matches);
    $matches = $matches[1];
    for ($i=0; $i<sizeof($matches);$i++)
      $matches[$i] = '/\%'.$matches[$i].'\%/';
    sort($matches);
    if (sizeof($matches) > 0 && is_array($parameters))
      $localizedMessage = preg_replace($matches, $parameters, $localizedMessage);
    
    return $localizedMessage;
  }
  /**
   * The getAll() method is used to get a localized list of all defined strings.
   * See Message::get() for more information.
   * This function only returns results if the ini file option 'usegettext' in section 'cms' is set
   * to 0.
   * @param lang The language, optional, default: ''.
   * @return An array of localized string
   */
  function getAll ($lang='')
  {
    if (!file_exists(BASE."wcmf/lib/util/class.InifileParser.php"))
      return array();
      
    global $MESSAGE_LANGUAGE;
    global $MESSAGE_LOCALE_DIR;
    
    // select language
    if ($lang == '')
    {
      if ($MESSAGE_LANGUAGE != '')
        $lang = $MESSAGE_LANGUAGE;
      else if ($lang == '')
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }      

    // convert lang to language_COUNTRY format if not already done
    $lang = preg_replace("/\-/", "_", $lang);
    $lang = preg_replace("/(\w+)_(\w+)/e", "'\\1_'.strtoupper('\\2')", $lang);
    // if _COUNTRY is missing, use language as country
    if (strpos($lang, '_') === false)
      $lang = $lang.'_'.strtoupper($lang);

    $parser = &InifileParser::getInstance();
    // try to use custom array definitions as dictionary
    $messageFile = $MESSAGE_LOCALE_DIR.$lang."/LC_MESSAGES/messages_".$lang.".php";
    if (file_exists($messageFile))
    {
      require($messageFile); // require_once does not work here !!!
      return ${"messages_$lang"};
    }
    return array();
  }
}
?>
