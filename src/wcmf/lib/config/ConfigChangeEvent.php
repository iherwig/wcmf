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
namespace wcmf\lib\config;

use wcmf\lib\core\Event;

/**
 * ConfigChangeEvent signals a change of the application configuration.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConfigChangeEvent extends Event {

  const NAME = __CLASS__;
}
?>
