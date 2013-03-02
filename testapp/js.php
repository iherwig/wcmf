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
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\presentation\Application;

/**
 * Script for inclusion of dynamic javascript files.
 * The following parameters are known:
 * file: The javascript file to include
 */
$application = new Application();
$application->initialize();

// deliver the requested javascript file
Header("content-type: application/x-javascript");
require_once($_GET['file']);
?>
