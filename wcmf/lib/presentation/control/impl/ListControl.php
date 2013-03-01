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
namespace wcmf\lib\presentation\control\impl;

use wcmf\lib\presentation\View;
use wcmf\lib\presentation\control\impl\BaseControl;

/**
 * ListControl handles controls with list values
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListControl extends BaseControl {

  /**
   * Array of known list strategy instances
   */
  private static $_listStrategies = array();

  /**
   * Set the concrete ListStrategy instances.
   * @param listStrategies Array of strategy instances
   */
  public static function setListStrategies($listStrategies) {
    self::$_listStrategies = $listStrategies;
  }

  /**
   * Get a list of key/value pairs defined by description.
   * @param definition A list definition for which a ListStrategy is registered
   *                 (@see Control::render)
   * @param value The selected value (maybe null, default: null)
   * @param nodeOid Serialized oid of the node containing this value (for determining remote oids) [default: null]
   * @param language The lanugage if Control should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return An assoziative array containing the key/value pairs
   * @note The method will try to translate values with Message::get().
   * Keys and values are encoded using htmlentities(string, ENT_QUOTES, 'UTF-8').
   */
  private function getListMap($definition, $value=null, $nodeOid=null, $language=null) {
    $map = array();
    // get type and list from description
    if (!strPos($definition, ':')) {
      throw new ConfigurationException("No type found in list definition: ".$definition);
    }
    else {
      list($type, $list) = preg_split('/:/', $definition, 2);
    }

    // build list
    if (isset(self::$_listStrategies[$type])) {
      $strategy = new self::$_listStrategies[$type];
      $map = $strategy->getListMap($list, $value, $nodeOid, $language);
    }
    else {
      throw new ConfigurationException('No ListStrategy implementation registered for '.$type);
    }

    // escape and translate all keys and values
    $result = array();
    foreach($map as $key => $value) {
      $key = htmlentities($key, ENT_QUOTES, 'UTF-8');
      $value = htmlentities($value, ENT_QUOTES, 'UTF-8');
      if (!function_exists('html_entity_decode')) {
        $key = html_entity_decode($key, ENT_QUOTES, 'UTF-8');
      }
      $result[strval($key)] = strval(Message::get($value));
    }
    return $result;
  }

  /**
   * Translate a value with use of it's assoziated input type e.g get the location string from a location id.
   * (this is only done when the input type has a list definition).
   * @param value The value to translate (maybe comma separated list for list controls)
   * @param inputType The description of the control as given in the input_type property of a value (see Control::render())
   * @param nodeOid Serialized oid of the node containing this value (for determining remote oids) [default: null]
   * @param language The lanugage if Control should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return The translated value
   */
  public function translateValue($value, $inputType, $nodeOid = null, $language=null) {
    // get definition and list from description
    $translated = '';
    if (strPos($inputType, '#') && $value != '') {
      $control = self::getControl($inputType);
      list(,$list) = preg_split('/#/', $inputType, 2);
      $map = $control->getListMap($list, $value, $nodeOid, $language);
      if ($list != '' && strPos($value, ',')) {
        $value = preg_split('/,/', $value);
      }
      if (is_array($value)) {
        foreach($value as $curValue) {
          $translated .= $map[$curValue].", ";
        }
        $translated = StringUtil::removeTrailingComma($translated);
      }
      else {
        $translated = $map[$value];
      }
      return $translated;
    }
    return $value;
  }

  /**
   * @see Control::assignViewValues
   */
  protected function assignViewValues(View $view) {
    // get the translated value
    $listMap = $view->getTemplateVars('listMap');
    $value = $view->getTemplateVars('value');
    $view->assign('translatedValue', $listMap[$value]);
  }
}
?>
