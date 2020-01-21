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
namespace wcmf\lib\presentation\control\lists;

/**
 * ListStrategy defines the interface for classes that
 * retrieve value lists.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ListStrategy {

  /**
   * Get a list of key/value pairs defined by the given configuration.
   * @param $options Associative array of implementation specific configuration
   * @param $valuePattern A regular expression pattern that the returned values should match (optional)
   * @param $key A key value, if only one item should be returned (optional)
   * @param $language The language if the values should be localized. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return An assoziative array containing the key/value pairs
   */
  public function getList($options, $valuePattern=null, $key=null, $language=null);

  /**
   * Check if the list values are static or changing.
   * @param $options Associative array of implementation specific configuration
   * @return Boolean
   */
  public function isStatic($options);
}
?>
