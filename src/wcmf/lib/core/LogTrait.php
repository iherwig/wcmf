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
namespace wcmf\lib\core;

trait LogTrait {
  private static ?Logger $logger = null;

  public static function logger(): Logger {
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    return self::$logger;
  }
}
?>