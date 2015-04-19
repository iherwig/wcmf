<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\control;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\util\StringUtil;

/**
 * ValueListProvider provides lists of key/values to be used
 * with list input controls.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValueListProvider {

  /**
   * Array of known list strategy instances
   */
  private static $_listStrategies = null;

  /**
   * Get a list of key/value pairs defined by the given configuration.
   * @param $definition The list definition as used in the input_type definition
   *                  in the 'list' parameter (e.g. '{"type":"config","section":"EntityStage"}')
   * @param $language The language if the values should be localized. Optional,
   *                  default is Localization::getDefaultLanguage()
   * @return An assoziative array with keys 'items' (array containing the key/value pairs)
   *                  and 'isStatic'
   */
  public static function getList($definition, $language=null) {

    $result = array();
    $decodedDefinition = json_decode($definition, true);
    if ($decodedDefinition === null) {
      throw new ConfigurationException("No valid JSON format: ".$definition);
    }
    if (!isset($decodedDefinition['type'])) {
      throw new ConfigurationException("No 'type' given in list definition: ".$definition);
    }

    // get the strategy
    $strategy = self::getListStrategy($decodedDefinition['type']);

    // build list
    $result['items'] = $strategy->getList($decodedDefinition, $language);
    $result['isStatic'] = $strategy->isStatic($decodedDefinition);

    return $result;
  }

  /**
   * Translate a value with use of it's assoziated input type e.g get the location string from a location id.
   * (this is only done when the input type has a list definition).
   * @param $value The value to translate (maybe comma separated list for list controls)
   * @param $inputType The description of the control as given in the 'input_type' property of a value
   * @param $language The language if the value should be localized. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return The translated value
   */
  public static function translateValue($value, $inputType, $language=null) {
    // get definition and list from inputType
    if (strPos($inputType, ':') && strlen($value) > 0) {
      $translated = '';
      list(, $options) = preg_split('/:/', $inputType, 2);
      $decodedOptions = json_decode($options, true);
      if ($decodedOptions === null) {
        throw new ConfigurationException("No valid JSON format: ".$options);
      }
      if (isset($decodedOptions['list'])) {
        $listDef = $decodedOptions['list'];
        $list = self::getList(json_encode($listDef), $language);
        $items = $list['items'];
        if (strPos($value, ',')) {
          $value = preg_split('/,/', $value);
        }
        if (is_array($value)) {
          foreach($value as $curValue) {
            $curValue = trim($curValue);
            $translated .= (isset($items[$curValue]) ? $items[$curValue] : $value).", ";
          }
          $translated = StringUtil::removeTrailingComma($translated);
        }
        else {
          $value = trim($value);
          $translated = isset($items[$value]) ? $items[$value] : $value;
        }
        return $translated;
      }
    }
    return $value;
  }

  /**
   * Get the ListStrategy instance for a given list type
   * @param $listType The list type
   * @return ListStrategy instance
   * @throws ConfigurationException
   */
  protected static function getListStrategy($listType) {
    // get list strategies
    if (self::$_listStrategies == null) {
      self::$_listStrategies = ObjectFactory::getInstance('listStrategies');
    }

    $strategy = null;

    // search strategy
    if (isset(self::$_listStrategies[$listType])) {
      $strategy = self::$_listStrategies[$listType];
    }
    else {
      throw new ConfigurationException('No ListStrategy implementation registered for '.$listType);
    }
    return $strategy;
  }
}
?>
