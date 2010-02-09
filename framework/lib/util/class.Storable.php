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
 * @class Storable
 * @ingroup Util
 * @brief This class defines the interface for classes that can be stored in the session.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Storable
{
  /**
   * Get the class definition files.
   * @return An array holding the filenames
   */
  function getClassDefinitionFiles();

  /**
   * Called when the object is retrieved from the session.
   * @note Subclasses will override this to implement their special requirements. The default implementation does nothing.
   */
  function loadFromSession();

  /**
   * Called when the object is stored in the session.
   * @note Subclasses will override this to implement their special requirements. The default implementation does nothing.
   */
  function saveToSession();
}