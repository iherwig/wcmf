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
 * @class NullView
 * @ingroup Presentation
 * @brief NullView is a stub class that implements all view methods.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullView
{
  function trigger_error($error_msg, $error_type = E_USER_WARNING)
  {
    WCMFException::throwEx("View error: $error_msg", __FILE__, __LINE__);
  }
  
  function setup() {}

  function clearAllCache() 
  {
    return true;
  }
  function clearCache($tplFile=null, $cacheId=null)
  {
    return true;
  }
  function isCached($tplFile, $cacheId=null)
  {
    return false;
  }
  
  function assign($tpl_var, $value=null) {}
  function assign_by_ref($tpl_var, &$value) {}
  function display($resource_name, $cache_id=null, $compile_id=null) {}
  function fetch($resource_name, $cache_id=null, $compile_id=null, $display=false) {}
  function &get_template_vars($name=null) {}
  function clear_all_assign() {}
}
?>
