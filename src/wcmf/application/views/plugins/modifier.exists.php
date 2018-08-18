<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
use wcmf\lib\io\FileUtil;

/**
 * Check if the given file path exists.
 *
 * Example:
 * @code
 * {if $file|exists}
 *    ...
 * {/if}
 * @endcode
 *
 * @param $path The file path
 * @return Boolean
 */
function smarty_modifier_exists($path) {
  return FileUtil::fileExists($path);
}
?>