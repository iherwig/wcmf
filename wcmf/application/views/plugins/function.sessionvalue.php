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
namespace wcmf\lib\presentation\smarty_plugins;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.sessionvalue.php
* Type:     function
* Name:     sessionvalue
* Purpose:  output a session variable value
* Usage:    e.g. {sessionvalue name="platform"}
* -------------------------------------------------------------
*/
function smarty_function_sessionvalue($params, &$smarty)
{
  $session = ObjectFactory::getInstance('session');
  echo $session->get($params['name']);
}
?>