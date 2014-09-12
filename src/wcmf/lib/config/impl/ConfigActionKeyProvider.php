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
namespace wcmf\lib\config;

use wcmf\lib\core\ObjectFactory;

/**
 * ConfigActionKeyProvider searches for action keys in the
 * application configuration.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConfigActionKeyProvider {

  private $_configSection = null;

  /**
   * Set the configuration section to search in.
   * @param $configSection String
   */
  public function setUserType($configSection) {
    $this->_configSection = $configSection;
  }

  /**
   * @see ActionKeyProvider::containsKey()
   */
  public function containsKey($actionKey) {
    $config = ObjectFactory::getConfigurationInstance();
    return $config->hasValue($actionKey, $this->_section);
  }
}
?>
