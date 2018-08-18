<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\core\ObjectFactory;

/**
 * Date names to be included by l10n tools
 * - $message->getText("Monday")
 * - $message->getText("Tuesday")
 * - $message->getText("Wednesday")
 * - $message->getText("Thursday")
 * - $message->getText("Friday")
 * - $message->getText("Saturday")
 * - $message->getText("Sunday")
 * - $message->getText("January")
 * - $message->getText("February")
 * - $message->getText("March")
 * - $message->getText("April")
 * - $message->getText("May")
 * - $message->getText("June")
 * - $message->getText("July")
 * - $message->getText("August")
 * - $message->getText("September")
 * - $message->getText("October")
 * - $message->getText("November")
 * - $message->getText("December")
 */

/**
 * Localize the month and day names in a date string
 *
 * Example:
 * @code
 * {$date|date_format:"F Y"|localize_date:'de'}
 * @endcode
 *
 * @param $date The date
 * @param $lang The language
 * @return String
 */
function smarty_modifier_localize_date($date, $lang) {
  // localize names
  $message = ObjectFactory::getInstance('message');
  $names = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday",
      "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
  ];
  foreach ($names as $name) {
    $localizeName = $message->getText($name, null, $lang);
    $localizeShortName = substr($localizeName, 0, 3);
    $date = strtr($date, [$name => $localizeName, substr($name, 0, 3) => $localizeShortName]);
  }
  return $date;
}
?>