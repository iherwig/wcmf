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
require_once(WCMF_BASE."wcmf/application/controller/class.TreeViewController.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.InternalLink.php");

/**
 * @class ResourceTreeController
 * @ingroup Controller
 * @brief ResourceTreeController allows to browse cms content in a tree view
 *        and set links when selecting a resource. It works together with
 *        resourcetree.tpl.
 *
 * <b>Input actions:</b>
 * - see TreeViewController
 *
 * <b>Output actions:</b>
 * - see TreeViewController
 * 
 * @param[in,out] fieldName The name of the input field, to which the selected value should be assigned
 * 
 * @author ingo herwig <ingo@wemove.com>
 */
class ResourceTreeController extends TreeViewController
{
  /**
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $result = parent::executeKernel();
    if ($this->hasView())
      $this->_response->setValue('fieldName', $this->_request->getValue('fieldName'));
    return $result;
  }
  /**
   * @see TreeViewController::getClickAction()
   */
  function getClickAction(&$node)
  {
    return "javascript:setUrl('".str_replace("'", "\'", InternalLink::makeLink($node->getOID()))."');";
  }
}
?>
