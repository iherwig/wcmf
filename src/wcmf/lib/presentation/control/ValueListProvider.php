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
namespace wcmf\lib\presentation\control;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
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
  private static $listStrategies = null;

  /**
   * Get a list of key/value pairs defined by the given configuration.
   * @param $definition The list definition as given in the input_type definition
   *                  in the 'list' parameter (e.g. '{"type":"config","section":"EntityStage"}')
   * @param $language The language if the values should be localized. Optional,
   *                  default is Localization::getDefaultLanguage()
   * @return An assoziative array with keys 'items' (array of arrays with keys 'key' and 'value'),
   *                  'isStatic' (indicating if the list may change of not)
   */
  public static function getList($definition, $language=null) {

    $decodedDefinition = json_decode($definition, true);
    if ($decodedDefinition === null) {
      throw new ConfigurationException("No valid JSON format: ".$definition);
    }
    if (!isset($decodedDefinition['type'])) {
      throw new ConfigurationException("No 'type' given in list definition: ".$definition);
    }

    // get the strategy
    $strategy = self::getListStrategy($decodedDefinition['type']);

    // add empty item, if defined
    $items = [];
    if (isset($decodedDefinition['emptyItem'])) {
      $emtpyItemText = $decodedDefinition['emptyItem'];
      $items[] = [
        'key' => null,
        'value' => ObjectFactory::getInstance('message')->getText($emtpyItemText, null, $language)
      ];
    }

    // build list
    foreach($strategy->getList($decodedDefinition, $language) as $key => $value) {
      $items[] = ['key' => $key, 'value' => $value];
    }

    return [
      'items' => $items,
      'isStatic' => $strategy->isStatic($decodedDefinition)
    ];
  }

  /**
   * Translate a value with use of it's assoziated input type e.g. get the location string from a location id.
   * (this is only done when the input type has a list definition).
   * @param $value The value to translate (might be a comma separated list for list controls)
   * @param $inputType The description of the control as given in the 'input_type' property of a value
   * @param $language The language if the value should be localized. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @param $itemDelim Delimiter string for array values (optional, default: ", ")
   * @return String
   */
  public static function translateValue($value, $inputType, $language=null, $itemDelim=", ") {
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
        // only split, if it's an array to avoid casting null values to strings
        if (strPos($value, ',')) {
          $value = preg_split('/,/', $value);
        }
        if (is_array($value)) {
          $translatedItems = [];
          foreach($value as $curValue) {
            $curValue = trim($curValue);
            $translatedItems[] = self::getItemValue($items, $curValue);
          }
          $translated = join($itemDelim, $translatedItems);
        }
        else {
          $translated = self::getItemValue($items, $value);
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
    if (self::$listStrategies == null) {
      self::$listStrategies = ObjectFactory::getInstance('listStrategies');
    }

    $strategy = null;

    // search strategy
    if (isset(self::$listStrategies[$listType])) {
      $strategy = self::$listStrategies[$listType];
    }
    else {
      throw new ConfigurationException('No ListStrategy implementation registered for '.$listType);
    }
    return $strategy;
  }

  /**
   * Get the value of the item with the given key. Returns the key, if it does
   * not exist in the list.
   * @param $list Array of associative arrays with keys 'key' and 'value'
   * @param $key The key to search
   * @return String
   */
  protected static function getItemValue($list, $key) {
    foreach ($list as $item) {
      // strict comparison for null value
      if (($key === null && $item['key'] === $key) || ($key !==null && $item['key'] == $key)) {
        return $item['value'];
      }
    }
    return $key;
  }
}
?>
