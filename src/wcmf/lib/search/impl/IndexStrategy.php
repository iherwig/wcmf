<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\search\impl;

use wcmf\lib\persistence\PersistentObject;

/**
 * IndexStrategy defines the interface for indexing implementations.
 */
interface IndexStrategy {
  
  /**
   * Get the lucene document for a PersistentObject
   * @return Document
   */
  public function getDocument(PersistentObject $obj);
  
  /**
   * Encode the given value according to the input type
   * @param $value
   * @param $inputType
   * @return String
   */
  public function encodeValue($value, $inputType);  
}
?>
