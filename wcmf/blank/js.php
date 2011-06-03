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
 */
require_once("base_dir.php");

// deliver the requested javascript file
Header("content-type: application/x-javascript");
require_once($_GET['file']);
?>
