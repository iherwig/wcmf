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
namespace wcmf\lib\config\impl;

use wcmf\lib\config\ActionKeyProvider;
use wcmf\lib\config\Configuration;

/**
 * ConfigActionKeyProvider searches for action keys in the
 * application configuration.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConfigActionKeyProvider implements ActionKeyProvider {

  private ?Configuration $configuration = null;
  private ?string $configSection = null;
  private ?string $id = null;

  /**
   * Constructor
   * @param Configurtion $configuration Configuration instance
   * @param string $configSection The configuration section to search in
   */
  public function __construct(Configuration $configuration, string $configSection) {
    $this->configuration = $configuration;
    $this->configSection = $configSection;
    $this->id = null;
  }

  /**
   * @see ActionKeyProvider::containsKey()
   */
  public function containsKey(string $actionKey): bool {
    return $this->configuration->hasValue($actionKey, $this->configSection);
  }

  /**
   * @see ActionKeyProvider::getKeyValue()
   */
  public function getKeyValue(string $actionKey): ?string {
    if ($this->containsKey($actionKey)) {
      return $this->configuration->getValue($actionKey, $this->configSection);
    }
    return null;
  }

  /**
   * @see ActionKeyProvider::getId()
   */
  public function getId(): string {
    if ($this->id == null) {
      $this->id = str_replace('\\', '.', __CLASS__).'.'.$this->configSection;
    }
    return $this->id;
  }
}
?>
