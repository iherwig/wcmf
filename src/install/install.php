<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>wCMF - Installation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="../app/public/vendor/twitter-bootstrap/css/bootstrap.css" rel="stylesheet">
  <link href="../app/public/vendor/twitter-bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
</head>
<body>
<div class="container">
<div class="page-header"><h1>Installation</h1></div>
<pre>
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
define('WCMF_BASE', realpath( dirname(__FILE__).'/..').'/');
error_reporting(E_ERROR | E_PARSE);

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\util\DBUtil;

Log::configure('log4php.properties');
Log::info("initializing wCMF database tables...", "install");

// get configuration from file
$configPath = realpath('../app/config/').'/';
$config = new InifileConfiguration($configPath);
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);

$permissionManager = ObjectFactory::getInstance('permissionManager');
$permissionManager->deactivate();

$persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
$transaction = $persistenceFacade->getTransaction();
$transaction->begin();
try {
  // initialize database sequence, create default user/role
  if(sizeof($persistenceFacade->getOIDs("DBSequence")) == 0) {
    Log::info("initializing database sequence...", "install");
    $seq = $persistenceFacade->create("DBSequence", BuildDepth::SINGLE);
    $seq->setValue("id", 1);
  }

  $roleTypeInst = ObjectFactory::getInstance('Role');
  $userTypeInst = ObjectFactory::getInstance('User');

  $adminRole = $roleTypeInst::getByName("administrators");
  if (!$adminRole) {
    Log::info("creating role with name 'administrators'...", "install");
    $adminRole = $persistenceFacade->create($roleTypeInst->getType());
    $adminRole->setName("administrators");
  }
  $adminUser = $userTypeInst::getByLogin("admin");
  if (!$adminUser) {
    Log::info("creating user with login 'admin' password 'admin'...", "install");
    $adminUser = $persistenceFacade->create($userTypeInst->getType());
    $adminUser->setLogin("admin");
    $adminUser->setPassword("admin");
    $adminUser->setName("Administrator");
    if (in_array("admin.ini", $config->getConfigurations())) {
      $adminUser->setConfig("admin.ini");
    }
  }
  if (!$adminUser->hasRole("administrators")) {
    Log::info("adding user 'admin' to role 'administrators'...", "install");
    $adminUser->addNode($adminRole);
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
?>
</pre>
</div>
</html>