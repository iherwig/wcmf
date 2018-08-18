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
namespace wcmf\lib\persistence\output;

use wcmf\lib\persistence\PersistentObject;

/**
 * OutputStrategy defines the interface for classes that write an
 * object's content to a destination (called 'document') using a special format.
 * OutputStrategy implements the 'Strategy Pattern'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface OutputStrategy {

  /**
   * Write the document header.
   */
  public function writeHeader();

  /**
   * Write the document footer.
   */
  public function writeFooter();

  /**
   * Write the object's content.
   * @param $obj The object to write.
   */
  public function writeObject(PersistentObject $obj);
}
?>
