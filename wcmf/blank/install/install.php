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
define("WCMF_BASE", realpath ("../../")."/");
error_reporting(E_ERROR | E_PARSE);

require_once(WCMF_BASE."wcmf/lib/util/Message.php");
require_once(WCMF_BASE."wcmf/lib/util/InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/util/FileUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/DBUtil.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/security/RightsManager.php");
require_once(WCMF_BASE."wcmf/lib/security/UserManager.php");
require_once(WCMF_BASE."wcmf/lib/util/ObjectFactory.php");
require_once(WCMF_BASE."wcmf/lib/util/Log.php");

Log::info("initializing wCMF database tables...", "install");

// get configuration from file
$CONFIG_PATH = WCMF_BASE.'application/include/';
$configFile = $CONFIG_PATH.'config.ini';
Log::info("configuration file: ".$configFile, "install");
$parser = InifileParser::getInstance();
if (!$parser->parseIniFile($configFile, true))
{
  Log::error($parser->getErrorMsg(), "install");
  exit();
}

// message globals
$GLOBALS['MESSAGE_LOCALE_DIR'] = $parser->getValue('localeDir', 'cms');
$GLOBALS['MESSAGE_LANGUAGE'] = $parser->getValue('language', 'cms');

// set locale
if ($GLOBALS['MESSAGE_LANGUAGE'] !== false) {
  setlocale(LC_ALL, $GLOBALS['MESSAGE_LANGUAGE']);
}
$rightsManager = RightsManager::getInstance();
$rightsManager->deactivate();

// initialize database sequence, create default user/role
$persistenceFacade = PersistenceFacade::getInstance();
if(sizeof($persistenceFacade->getOIDs("Adodbseq")) == 0)
{
  Log::info("initializing database sequence...", "install");
  $seq = $persistenceFacade->create("Adodbseq", BUILDDEPTH_SINGLE);
  $seq->setValue("id", 1);
  $seq->save();
}
$userManager = ObjectFactory::createInstanceFromConfig('implementation', 'UserManager');
$userManager->beginTransaction();
if (!$userManager->getRole("administrators"))
{
  Log::info("creating role with name 'administrators'...", "install");
  $userManager->createRole("administrators");
}
if (!$userManager->getUser("admin"))
{
  Log::info("creating user with login 'admin' password 'admin'...", "install");
  $userManager->createUser("Administrator", "", "admin", "admin", "admin");
  $userManager->setUserProperty("admin", USER_PROPERTY_CONFIG, "admin.ini");
}
$admin = $userManager->getUser("admin");
if ($admin && !$admin->hasRole('administrators'))
{
  Log::info("adding user 'admin' to role 'administrators'...", "install");
  $userManager->addUserToRole("administrators", "admin");
}
$userManager->commitTransaction();

// execute custom scripts from the directory 'custom-install'
if (is_dir('custom-install'))
{
  $sqlScripts = FileUtil::getFiles('custom-install', '/[^_]+_.*\.sql$/', true);
  sort($sqlScripts);
  foreach ($sqlScripts as $script)
  {
    // extract the initSection from the filename
    $initSection = array_shift(preg_split('/_/', basename($script)));
    DBUtil::executeScript($script, $initSection);
  }
}

Log::info("done.", "install");
