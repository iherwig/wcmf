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
error_reporting(E_ALL | E_PARSE);

require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/core/AutoLoader.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Application.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.ActionMapper.php");
require_once(WCMF_BASE."wcmf/lib/util/class.SearchUtil.php");

$application = Application::getInstance();
try {
  // initialize the application
  $request = $application->initialize();

  // process the requested action
  ActionMapper::processAction($request);

  // store the last successful request
  SessionData::getInstance()->set('lastRequest', $request);
}
catch (Exception $ex) {
  $application->handleException($ex);
}
?>