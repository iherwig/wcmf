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
require_once(WCMF_BASE."wcmf/lib/presentation/ControllerMessage.php");

/**
 * @class Request
 * @ingroup Presentation
 * @brief Request holds the request values that are used as input to 
 * Controller instances. It is typically instantiated and filled by the 
 * ActionMapper.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Request extends ControllerMessage
{
  var $_responseFormat = null;

  /**
   * Set the desired response format
   * @param format One of the MSG_FORMAT constants
   */
  function setResponseFormat($format)
  {
    $this->_responseFormat = $format;
  }
  /**
   * Get the desired response format
   * @return One of the MSG_FORMAT constants
   */
  function getResponseFormat()
  {
    return $this->_responseFormat;
  }
}
?>
