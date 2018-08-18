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
namespace wcmf\lib\search\impl;

use ZendSearch\Lucene\Analysis\Analyzer\Common\Utf8Num\CaseInsensitive;

class LuceneUtf8Analyzer extends CaseInsensitive {
  /**
   * Override method to make sure we are using utf-8
   */
  public function setInput($data, $encoding = '') {
    parent::setInput($data, 'UTF-8');
  }
}
?>
