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
require_once(BASE."wcmf/lib/presentation/class.ControllerMessage.php");

/**
 * @class Response
 * @ingroup Presentation
 * @brief Response holds the response values that are used as output from 
 * Controller instances. It is typically instantiated by the ActionMapper 
 * instance and filled during Controller execution.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Response extends ControllerMessage
{
  var $_view = null;
  
  /**
   * Set the view for the output if necessary (e.g. for html response format)
   * @param view A reference to a View instance
   */
  function setView(&$view)
  {
    $this->_view = &$view;
  }
  /**
   * Get the view for the output
   * @return A reference to a View instance
   */
  function &getView()
  {
    return $this->_view;
  }
}
?>
