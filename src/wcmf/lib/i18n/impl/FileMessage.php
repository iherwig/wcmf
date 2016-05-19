<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\i18n\impl;

use wcmf\lib\i18n\Message;
use wcmf\lib\io\FileUtil;

/**
 * FileMessage retrieves localized messages from files, that are
 * stored in the localization directory. Inside this directory there must
 * be a messages_$lang.php files for each language defining the translation
 * for each message in an associative array called $messages.
 *
 * For example the messages_de file could have the following content:
 * @code
 * $messages = array(
 *   'up' => 'hoch',
 *   'down' => 'runter',
 *   ...
 * );
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileMessage implements Message {

  private $localeDir;
  private $language;

  /**
   * Cache for loaded translations
   */
  private $translations = array();

  /**
   * Constructor
   * @param $localeDir The directory
   * @param $language
   */
  public function __construct($localeDir, $language='') {
    $this->localeDir = FileUtil::realpath(WCMF_BASE.$localeDir).'/';
    if (strlen($language) > 0) {
      $this->language = $language;
    }
    else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $this->language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }
  }

  /**
   * @see Message::getText()
   */
  public function getText($message, $parameters=null, $lang='') {
    // get the translations
    $lang = strlen($lang) == 0 ? $this->language : $lang;
    $translations = $this->getTranslations($lang);
    $localizedMessage = $message;
    if (isset($translations[$message]) && strlen($translations[$message]) > 0) {
      $localizedMessage = $translations[$message];
    }

    // replace parameters
    preg_match_all("/%([0-9]+)%/", $localizedMessage, $matches);
    $matches = $matches[1];
    for ($i=0, $count=sizeof($matches); $i<$count; $i++) {
      $matches[$i] = '/\%'.$matches[$i].'\%/';
    }
    sort($matches);
    if (sizeof($matches) > 0 && is_array($parameters)) {
      $localizedMessage = preg_replace($matches, $parameters, $localizedMessage);
    }
    return $localizedMessage;
  }

  /**
   * @see Message::getAll()
   */
  public function getAll($lang='') {
    // get the translations
    $translations = $this->getTranslations($lang);
    return $translations;
  }

  /**
   * Get all translations for a language.
   * @param $lang The language (optional, default: '')
   * @return The translations as associative array
   */
  private function getTranslations($lang) {
    if (!isset($this->translations[$lang])) {
      $messageFile = $this->localeDir."/messages_".$lang.".php";
      if (file_exists($messageFile)) {
        require_once($messageFile);
        // store for later reference
        $this->translations[$lang] = ${"messages_$lang"};
      }
      else {
        $this->translations[$lang] = array();
      }
    }
    return $this->translations[$lang];
  }
}
?>
