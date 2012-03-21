<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\persistence;

use wcmf\lib\persistence\PersistentObject;

/**
 * IOutputStrategy defines the interface for classes that write an
 * object's content to a destination (called 'document') using a special format.
 * OutputStrategy implements the 'Strategy Pattern'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface IOutputStrategy {

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
   * @param obj The object to write.
   */
  public function writeObject(PersistentObject $obj);
}
?>