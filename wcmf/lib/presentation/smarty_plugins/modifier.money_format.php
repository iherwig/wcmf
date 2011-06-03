<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
 
/**
 * Smarty money_format modifier plugin
 *
 * Type:     modifier<br>
 * Name:     money_format<br>
 * Purpose:  Formats a number as a html-encoded currency string
 * @link http://www.php.net/money_format
 * @param float
 * @param integer (default 0)
 * @return string (html-encoded)
 */
function smarty_modifier_money_format($number, $leftFill='0')
{
  if (function_exists('money_format'))
  {
    setlocale(LC_MONETARY, 'de_DE.UTF8');
    $format = (!empty($leftFill))?'%!#'.$leftFill.'n':'%!n';
    return str_replace(' ','&nbsp;',money_format($format, $number));
  }
  else {
    return $number;
  }
}
?>