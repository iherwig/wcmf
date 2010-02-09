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
require_once(BASE."wcmf/lib/presentation/format/class.IFormat.php");

/**
 * @class NullFormat
 * @ingroup Format
 * @brief NullFormat passes through the original request and response objects
 * without modifying or transforming them.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullFormat extends IFormat
{
  /**
   * @see IFormat::deserialize()
   */
  function deserialize(&$request) {}
  
  /**
   * @see IFormat::serialize()
   */
  function serialize(&$response) {}
}
?>
