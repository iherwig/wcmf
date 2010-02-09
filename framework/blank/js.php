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
error_reporting(E_ERROR | E_PARSE);

/**
 * Script for inclusion of dynamic javascript files.
 * The following parameters are known:
 * file: The javascript file to include
 * initApp: 0/1 wether to initialize the wCMF application (read configuration etc.)
 *          This causes some overhead, but gives you the ability to use wCMF messages, logging
 *          etc. using php inside the js file
 */
require_once("base_dir.php");
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/util/class.URIUtil.php");

if ($_GET['initApp'] == 1)
{
  // initialize the application
  require_once(BASE."wcmf/lib/presentation/class.Application.php");
  $application = &Application::getInstance();
  $application->setupGlobals();
}

// the script url
$SCRIPT_URL = UriUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

// the application url (used for Ajax calls)
$APP_URL = URIUtil::makeAbsolute("main.php", $SCRIPT_URL);

// wcmf parameters
$controller = $_GET['controller'];
$context = $_GET['context'];

// deliver the requested javascript file
Header("content-type: application/x-javascript");
require_once($_GET['file']);
?>
