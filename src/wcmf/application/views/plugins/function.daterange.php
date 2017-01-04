<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\core\ObjectFactory;

/**
 * Format two dates avoiding duplicate years and months (e.g. 10. - 11.03.2016).
 *
 * Example:
 * @code
 * {daterange from=$date->getValue('start_date') to=$date->getValue('end_date'}
 * @endcode
 *
 * @param $params Array with keys:
 *        - from: Start date in a format that can be parsed into a DateTime instance
 *        - to: End date in a format that can be parsed into a DateTime instance
 *        - lang: Language used to translate the date format (d.m.Y)
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_daterange(array $params, Smarty_Internal_Template $template) {
  $from = new DateTime($params['from']);
  $to = new DateTime($params['to']);
  $lang = $params['lang'];

  $sameDay = $from->format('Y-m-d') == $to->format('Y-m-d');
  $sameMonth = $from->format('Y-m') == $to->format('Y-m');
  $sameYear = $from->format('Y') == $to->format('Y');

  $message = ObjectFactory::getInstance('message');
  $fullFormat = $message->getText('d.m.Y', null, $lang);
  $monthFormat = $message->getText('d.m.', null, $lang);
  $dayFormat = $message->getText('d.', null, $lang);

  if ($sameDay) {
    return $from->format($fullFormat);
  }
  elseif ($sameMonth) {
    return $from->format($dayFormat)." - ".$to->format($fullFormat);
  }
  elseif ($sameYear) {
    return $from->format($monthFormat)." - ".$to->format($fullFormat);
  }
  return $from->format($fullFormat)." - ".$to->format($fullFormat);
}
?>