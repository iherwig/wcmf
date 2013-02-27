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

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     resource.lib.php
 * Type:     resource
 * Name:     lib
 * Purpose:  Fetches templates from lib directories
 * -------------------------------------------------------------
 */
function smarty_resource_lib_source($tpl_name, $tpl_source, $smarty)
{
  $file = get_path($tpl_name);
  if (is_file($file)) {
    $tpl_source = file_get_contents($file);
    return true;
  }
  return false;
}

function smarty_resource_lib_timestamp($tpl_name, $tpl_timestamp, $smarty)
{
  $file = get_path($tpl_name);
  if (is_file($file)) {
    $tpl_timestamp = filemtime($file);
    return true;
  }
  return false;
}

function smarty_resource_lib_secure($tpl_name, $smarty)
{
  return true;
}

function smarty_resource_lib_trusted($tpl_name, $smarty)
{
  return true;
}

function get_path($path)
{
  $config = ObjectFactory::getConfigurationInstance();

  // check for overrides in templateDir first
  $templateDir = $config->getValue('templateDir', 'smarty');
  $userTpl = realpath($templateDir."/".$path);
  if (file_exists($userTpl)) {
    return $userTpl;
  }
  // use templates from libDir
  if (($libDir = $config->getValue('libDir', 'application')) === false) {
    throw new ConfigurationException("No library path 'libDir' defined in ini section 'application'.", __FILE__, __LINE__);
  }
  return realpath($libDir."/".$path);
}
?>