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
define('WCMF_BASE', realpath( dirname(__FILE__).'/../..').'/');
error_reporting(E_ERROR | E_PARSE);

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\util\DBUtil;

Log::configure('log4php.properties');
Log::info("initializing wCMF database tables...", "install");

// get configuration from file
$configPath = realpath('../config/').'/';
$configFile = $configPath.'config.ini';
Log::info("configuration file: ".$configFile, "install");
$config = new InifileConfiguration($configPath);
$config->addConfiguration($configFile);

$permissionManager = ObjectFactory::getInstance('permissionManager');
$permissionManager->deactivate();

$persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
$userManager = ObjectFactory::getInstance('userManager');
$transaction = $persistenceFacade->getTransaction();
$transaction->begin();
try {
  // initialize database sequence, create default user/role
  if(sizeof($persistenceFacade->getOIDs("Adodbseq")) == 0) {
    Log::info("initializing database sequence...", "install");
    $seq = $persistenceFacade->create("Adodbseq", BuildDepth::SINGLE);
    $seq->setValue("id", 1);
  }

  if (!$userManager->getRole("administrators")) {
    Log::info("creating role with name 'administrators'...", "install");
    $userManager->createRole("administrators");
  }
  if (!$userManager->getUser("admin")) {
    Log::info("creating user with login 'admin' password 'admin'...", "install");
    $userManager->createUser("Administrator", "", "admin", "admin", "admin");
    $userManager->setUserProperty("admin", USER_PROPERTY_CONFIG, "admin.ini");
  }
  $admin = $userManager->getUser("admin");
  if ($admin && !$admin->hasRole('administrators')) {
    Log::info("adding user 'admin' to role 'administrators'...", "install");
    $userManager->addUserToRole("administrators", "admin");
  }

  // execute custom scripts from the directory 'custom-install'
  if (is_dir('custom-install')) {
    $sqlScripts = FileUtil::getFiles('custom-install', '/[^_]+_.*\.sql$/', true);
    sort($sqlScripts);
    foreach ($sqlScripts as $script) {
      // extract the initSection from the filename
      $initSection = array_shift(preg_split('/_/', basename($script)));
      DBUtil::executeScript($script, $initSection);
    }
  }
  $transaction->commit();
  Log::info("done.", "install");
}
catch (Exception $ex) {
  Log::error($ex, "install");
  $transaction->rollback();
}
