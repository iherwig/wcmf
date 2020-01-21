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
 *        - formats: Array of maximum 3 date formats (day-month-year, day-month, day) to use, wich will be used to format
 *            the first date according to the relation to the second date which uses the full format (optional, default: ['d.m.Y', 'd.m.', 'd.'])
 *            If displaytime is true, the default formats are ['d.m.Y H:i', 'd.m.Y H:i', 'H:i'] and the format is used for the second date
 *            If less than 3 formats are specified, last format in the list will be used for unspecified formats
 *        - delim: Delimiter string to be used to separate the values (optional, default: ' – ' (&ndash;))
 *        - formattype: Format type used in formats parameter (optional, 'strftime'|'date'|'auto', default: 'auto')
 *        - displaytime: Also take time into account (optional, default: false)
 * @note The automatic decision for a format type is based on the existance of a % char in the formats parameter.
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

  $displayTime = isset($params['displaytime']) ? $params['displaytime'] : false;

  $sameDay = $from->format('Y-m-d') == $to->format('Y-m-d');
  $sameMonth = $from->format('Y-m') == $to->format('Y-m');
  $sameYear = $from->format('Y') == $to->format('Y');
  $sameTime = $from->format('H:i') == $to->format('H:i');

  $formats = !$displayTime ? ['d.m.Y', 'd.m.', 'd.'] : ['d.m.Y H:i', 'd.m.Y H:i', 'H:i'];
  if (isset($params['formats']) && is_array($params['formats'])) {
    $userFormats = $params['formats'];
    $numFormats = sizeof($userFormats);
    $formats = $numFormats >= 3 ? array_slice($userFormats, 0, 3) : array_pad($userFormats, 3, $userFormats[$numFormats-1]);
  }
  $fullFormat = $formats[0];
  $monthFormat = $formats[1];
  $dayFormat = $formats[2];

  $delim = isset($params['delim']) ? $params['delim'] : ' – ';

  $formatType = isset($params['formattype']) ? $params['formattype'] : 'auto';
  if ($formatType == 'auto') {
    $formatType = strpos(join('', $formats), '%') === false ? 'date' : 'strftime';
  }

  $formatFunction = function(DateTime $date, $format) use ($formatType) {
    return $formatType == 'date' ? $date->format($format) : strftime($format, $date->getTimestamp());
  };

  if ($displayTime) {
    if ($sameDay && $sameTime) {
      $result = $formatFunction($from, $fullFormat);
    }
    elseif ($sameDay) {
      $result = $formatFunction($from, $fullFormat).$delim.$formatFunction($to, $dayFormat);
    }
    else {
      $result = $formatFunction($from, $fullFormat).$delim.$formatFunction($to, $fullFormat);
    }
  }
  else {
    if ($sameDay) {
      $result = $formatFunction($from, $fullFormat);
    }
    elseif ($sameMonth) {
      $result = $formatFunction($from, $dayFormat).$delim.$formatFunction($to, $fullFormat);
    }
    elseif ($sameYear) {
      $result = $formatFunction($from, $monthFormat).$delim.$formatFunction($to, $fullFormat);
    }
    else {
      $result = $formatFunction($from, $fullFormat).$delim.$formatFunction($to, $fullFormat);
    }
  }
  return $result;
}
?>