<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\i18n;

/**
 * Message is used to get localized messages to be used in the user interface.

 * @note The language of a message is determined in one of 3 ways (in this order):
 * -# use the value of the lang parameter passed to Message::getText()
 * -# use the value of language from the Message configuration section
 * -# use the value of the global variable $_SERVER['HTTP_ACCEPT_LANGUAGE']
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Message {

  /**
   * Get a localized string.
   * @note It is not recommended to use this method with concatenated strings because this
   * restricts the positions of words in translations. E.g. 'She was born in %0% on %1%'
   * translates to the german sentence 'Sie wurde am %1% in %0% geboren' with the variables
   * flipped.
   * @note Implementations must return the original message, if no translation is found,
   * or the translation string is empty.
   * @param $message The message to translate (%0%, %1%, ... will be replaced by given parameters).
   * @param $parameters An array of values for parameter substitution in the message.
   * @param $lang The language (optional, default: '')
   * @return The localized string
   */
  public function getText($message, $parameters=null, $lang='');

  /**
   * Get a list of all localized strings.
   * @param $lang The language (optional, default: '')
   * @return An array of localized strings
   */
  public function getAll($lang='');
}
?>
