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
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\security\principal\Role;

/**
 * Anonymous role
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AnonymousRole implements Role {

  const NAME = 'anonymous';

  /**
   * @see Role::setName()
   */
  public function setName(string $name): void {}

  /**
   * @see Role::getName()
   */
  public function getName(): string {
    return self::NAME;
  }
}
?>
