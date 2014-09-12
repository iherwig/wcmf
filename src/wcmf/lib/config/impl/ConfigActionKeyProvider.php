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
namespace wcmf\lib\config\impl;

use wcmf\lib\config\ActionKeyProvider;
use wcmf\lib\core\ObjectFactory;

/**
 * ConfigActionKeyProvider searches for action keys in the
 * application configuration.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConfigActionKeyProvider implements ActionKeyProvider {

  private $_configSection = null;

  /**
   * Set the configuration section to search in.
   * @param $configSection String
   */
  public function setConfigSection($configSection) {
    $this->_configSection = $configSection;
  }

  /**
   * @see ActionKeyProvider::containsKey()
   */
  public function containsKey($actionKey) {
    $config = ObjectFactory::getConfigurationInstance();
    return $config->hasValue($actionKey, $this->_configSection);
  }

  /**
   * @see ActionKeyProvider::getCacheId()
   */
  public function getCacheId() {
    return __CLASS__.'.'.$this->_configSection;
  }
}
?>
