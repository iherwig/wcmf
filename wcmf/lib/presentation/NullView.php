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
namespace wcmf\lib\presentation;

use wcmf\lib\presentation\IView;

/**
 * NullView is a stub class that implements all view methods.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullView implements IView
{
  public function setup() {}

  public function clearAllCache()
  {
    return true;
  }
  public function clearCache($tplFile=null, $cacheId=null)
  {
    return true;
  }
  public function isCached($tplFile, $cacheId=null)
  {
    return false;
  }

  public function assign($tpl_var, $value=null) {}
  public function assignByRef($tpl_var, &$value) {}
  public function display($resource_name, $cache_id=null, $compile_id=null) {}
  public function fetch($resource_name, $cache_id=null, $compile_id=null, $display=false) {}
  public function getTemplateVars($name=null) {}
  public function clearAllAssign() {}
}
?>
