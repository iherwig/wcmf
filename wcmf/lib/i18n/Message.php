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
namespace wcmf\lib\i18n;

use wcmf\lib\config\InifileParser;

/**
 * Message is used to get localized messages.
 * The localization directory must be given in the configuration value 'localeDir' in section 'cms'.
 * Inside this directory there must be a messages_$lang.php files for each language
 * defining the translation for each message.
 * For example the messages_de_DE file could have the following content:
 * @code
 * $messages_de_DE = array(
 *   'up' => 'hoch',
 *   'down' => 'runter',
 *   ...
 * );
 * @endcode
 * @note The language is determined in one of 3 ways (in this order):
 * -# use the value of the configuration value 'language' in section 'cms'
 * -# use the value of the global variable $_SERVER['HTTP_ACCEPT_LANGUAGE']
 * -# use the value of the lang parameter passed to Message::get()
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Message {

  private static $localeDir;
  private static $language;
  private static $initialized = false;

  /**
   * Cache for loaded translations
   */
  private static $translations = array();

  /**
   * Initialize static members
   */
  private static function initialize() {
    $parser = InifileParser::getInstance();
    self::$localeDir = $parser->getValue('localeDir', 'cms');
    self::$language = $parser->getValue('language', 'cms');
    setlocale(LC_ALL, self::$language);
    self::$initialized = true;
  }

  /**
   * Get a localized string.
   * @note It is not recommended to use this method with concatenated strings because this
   * restricts the positions of words in translations. E.g. 'She was born in %1% on %2%'
   * translates to the german sentance 'Sie wurde am \%2% in \%1% geboren' with the variables
   * flipped.
   * @param message The message to translate (\%0%, \%1%, ... will be replaced by given parameters).
   * @param parameters An array of values for parameter substitution in the message.
   * @param lang The language, optional, default: ''.
   * @return The localized string
   */
  public static function get($message, $parameters=null, $lang='') {
    if (!self::$initialized) {
      self::initialize();
    }

    // get the translations
    $translations = self::getTranslations($lang);
    if (isset($translations[$message])) {
      $localizedMessage = $translations[$message];
    }
    else {
      $localizedMessage = $message;
    }

    // replace parameters
    preg_match_all("/%([0-9]+)%/", $localizedMessage, $matches);
    $matches = $matches[1];
    for ($i=0; $i<sizeof($matches); $i++) {
      $matches[$i] = '/\%'.$matches[$i].'\%/';
    }
    sort($matches);
    if (sizeof($matches) > 0 && is_array($parameters)) {
      $localizedMessage = preg_replace($matches, $parameters, $localizedMessage);
    }
    return $localizedMessage;
  }
  /**
   * The getAll() method is used to get a localized list of all defined strings.
   * See Message::get() for more information.
   * @param lang The language, optional, default: ''.
   * @return An array of localized string
   */
  public static function getAll($lang='') {
    if (!self::$initialized) {
      self::initialize();
    }
    // get the translations
    $translations = self::getTranslations($lang);
    return $translations;
  }

  /**
   * Get the requested language in the language_COUNTRY format
   * @param lang The language, optional, default: ''.
   * @return The language code
   */
  private static function getQualifiedLanguage($lang) {
    // select language
    if ($lang == '') {
      if (strlen(self::$language) > 0) {
        $lang = self::$language;
      }
      else if ($lang == '' && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
      }
    }

    // convert lang to language_COUNTRY format if not already done
    $lang = preg_replace("/\-/", "_", $lang);
    $lang = preg_replace("/(\w+)_(\w+)/e", "'\\1_'.strtoupper('\\2')", $lang);
    // if _COUNTRY is missing, use language as country
    if (strpos($lang, '_') === false) {
      $lang = $lang.'_'.strtoupper($lang);
    }
    return $lang;
  }

  /**
   * Get all translations for a language.
   * @param lang The language, optional, default: ''.
   * @return The translations as associative array
   */
  private static function getTranslations($lang) {
    if (!isset(self::$translations[$lang])) {
      $qualifiedLang = self::getQualifiedLanguage($lang);

      $messageFile = self::$localeDir."/messages_".$qualifiedLang.".php";
      if (file_exists($messageFile)) {
        require_once($messageFile);
        // store as requested and qualified language for later reference
        self::$translations[$lang] = ${"messages_$lang"};
        self::$translations[$qualifiedLang] = ${"messages_$lang"};
      }
    }
    return self::$translations[$lang];
  }
}
?>
