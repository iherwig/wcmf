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

/**
 * @interface OutputStrategy
 * @ingroup Output
 * @brief OutputStrategy is used to write an object's content
 * to a destination (called 'document') using a special format.
 * OutputStrategy implements the 'Strategy Pattern'.
 * The abstract base class OutputStrategy defines the interface for all
 * specialized OutputStrategy classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface OutputStrategy
{
  /**
   * Write the document header.
   */
  function writeHeader();

  /**
   * Write the document footer.
   */
  function writeFooter();

  /**
   * Write the object's content.
   * @param obj The object to write.
   */
  function writeObject($obj);
}
?>
