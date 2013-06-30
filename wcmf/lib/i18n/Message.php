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

use wcmf\lib\core\ObjectFactory;

/**
 * Message is used to get localized messages to be used in the user interface.
 * The localization directory must be given in the configuration value
 * 'localeDir' in section 'application'.
 * Inside this directory there must be a messages_$lang.php files for each
 * language defining the translation for each message in an associative array
 * called $messages.
 *
 * For example the messages_de file could have the following content:
 * @code
 * $messages = array(
 *   'up' => 'hoch',
 *   'down' => 'runter',
 *   ...
 * );
 * @endcode
 * @note The language is determined in one of 3 ways (in this order):
 * -# use the value of the lang parameter passed to Message::get()
 * -# use the value of the configuration value 'language' in section 'application'
 * -# use the value of the global variable $_SERVER['HTTP_ACCEPT_LANGUAGE']
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
    $config = ObjectFactory::getConfigurationInstance();
    self::$localeDir = $config->getValue('localeDir', 'application');
    if ($config->hasValue('language', 'application')) {
      self::$language = $config->getValue('language', 'application');
    }
    else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      self::$language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }
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
    $lang = strlen($lang) == 0 ? self::$language : $lang;
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
   * Get all translations for a language.
   * @param lang The language, optional, default: ''.
   * @return The translations as associative array
   */
  private static function getTranslations($lang) {
    if (!isset(self::$translations[$lang])) {
      $messageFile = self::$localeDir."/messages_".$lang.".php";
      if (file_exists($messageFile)) {
        require_once($messageFile);
        // store for later reference
        $messages = ${"messages_$lang"};
        $encodedMessage = array();
        foreach ($messages as $key => $value) {
          $encodedMessage[utf8_encode($key)] = utf8_encode($value);
        }
        self::$translations[$lang] = $encodedMessage;
      }
      else {
        self::$translations[$lang] = array();
      }
    }
    return self::$translations[$lang];
  }
}
?>
