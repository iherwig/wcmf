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
 * $Id: class.NullView.php 1148 2010-02-09 02:08:44Z iherwig $
 */

/**
 * @interface IView
 * @ingroup Presentation
 * @brief IView defines the interface for all view implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface IView
{
  function setup();

  function clearAllCache();

  function clearCache($tplFile=null, $cacheId=null);

  function isCached($tplFile, $cacheId=null);
  
  function assign($tpl_var, $value=null);
  
  function assignByRef($tpl_var, &$value);
  
  function display($resource_name, $cache_id=null, $compile_id=null);
  
  function fetch($resource_name, $cache_id=null, $compile_id=null, $display=false);
  
  function getTemplateVars($name=null);
  
  function clearAllAssign();
}
?>
