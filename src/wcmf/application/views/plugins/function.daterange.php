<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */

/**
 * Format one or two dates avoiding duplicate years and months (e.g. 10. - 11.03.2016).
 *
 * Example:
 * @code
 * {daterange from='2018-01-01' to='2018-03-01' lang='de'}
 * @endcode
 *
 * @param $params Array with keys:
 *        - from: Start date in a format that can be parsed into a DateTime instance
 *        - to: End date in a format that can be parsed into a DateTime instance
 *        - lang: Language used to translate the date names
 *        - formats: Array of date formats (day-month-year, day-month, day) to use (optional, default: ['d.m.Y', 'd.m.', 'd.'])
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_daterange(array $params, Smarty_Internal_Template $template) {
  $hasFrom = strlen($params['from']) > 0;
  $hasTo = strlen($params['to']) > 0;
  if (!$hasFrom && !$hasTo) {
    return '';
  }

  if ($hasFrom && !$hasTo) {
    $params['to'] = $params['from'];
  }
  elseif (!$hasFrom && $hasTo) {
    $params['from'] = $params['to'];
  }

  $from = new DateTime($params['from']);
  $to = new DateTime($params['to']);
  $lang = $params['lang'];

  $sameDay = $from->format('Y-m-d') == $to->format('Y-m-d');
  $sameMonth = $from->format('Y-m') == $to->format('Y-m');
  $sameYear = $from->format('Y') == $to->format('Y');

  $formats = isset($params['formats']) && is_array($params['formats']) && sizeof($params['formats']) == 3 ? $params['formats'] : ['d.m.Y', 'd.m.', 'd.'];
  $fullFormat = $formats[0];
  $monthFormat = $formats[1];
  $dayFormat = $formats[2];

  if ($sameDay) {
    $result = $from->format($fullFormat);
  }
  elseif ($sameMonth) {
    $result = $from->format($dayFormat)." - ".$to->format($fullFormat);
  }
  elseif ($sameYear) {
    $result = $from->format($monthFormat)." - ".$to->format($fullFormat);
  }
  else {
    $result = $from->format($fullFormat)." - ".$to->format($fullFormat);
  }
  return $result;
}
?>