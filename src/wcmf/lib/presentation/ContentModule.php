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
namespace wcmf\lib\presentation;

/**
 * Interface for content modules.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ContentModule {

  /**
   * Render the content
   */
  public function render();
}
?>