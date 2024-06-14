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
 * Interface for smarty content modules.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ContentModule {
  /**
   * Initialize the instance
   * @param \Smarty\Template $parentTemplate Template object that includes this content module
   * @param $params Associative array of parameters passed to the smarty {module} tag
   */
  public function initialize(\Smarty\Template $parentTemplate, array $params);

  /**
   * Render the content
   */
  public function render();
}
?>